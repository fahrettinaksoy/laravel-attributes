<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StructureModel extends Command
{
    protected $signature = 'structure:model {--Requests} {--Migration}';
    protected $description = 'Generate Models, Field traits, Pivot Models and optionally FormRequests from /structure JSON files.';

    public function handle(): void
    {
        $timestamp = date('Y_m_d_His');
        $basePath = base_path('structure');
        $jsonFiles = File::allFiles($basePath);

        foreach ($jsonFiles as $file) {
            $json = json_decode(File::get($file->getRealPath()), true);

            if (!$json || !isset($json['model'])) {
                $this->error("⚠️ Skipped: {$file->getRelativePathname()} — missing 'model' key");
                continue;
            }

            $this->generateModelAndField($file, $json, $jsonFiles);

            if ($this->option('Requests')) {
                $this->generateRequests($file, $json);
            }

            if ($this->option('Migration')) {

                $info = $this->resolveModelInfo($file);
                $this->generateMigration($file, $json, $info, $timestamp);
            }
        }

        $this->info("\n✅ structure:model completed — Models, Fields, Relations and Requests generated.\n");
    }

    /**
     * @return array{namespaceParts: array<int,string>, modelName: string, isPivot: bool, segments: array<int,string>, filename: string}
     */
    private function resolveModelInfo($file): array
    {
        $relative = str_replace(['\\', '/'], '/', $file->getRelativePath());
        $segments = array_filter(explode('/', $relative));
        $segmentsStudly = array_map(fn($s) => Str::studly($s), $segments);

        $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $isPivot = Str::contains($filename, '_');

        if ($isPivot) {
            [$parent, $child] = explode('_', $filename, 2);
            $parentStudly = Str::studly($parent);
            $childStudly = Str::studly($child);

            if (empty($segmentsStudly) || end($segmentsStudly) !== $parentStudly) {
                $segmentsStudly[] = $parentStudly;
            }

            $segmentsStudly[] = 'Pivots';
            $segmentsStudly[] = $parentStudly . $childStudly;
            $modelName = $parentStudly . $childStudly;
        } else {
            $modelName = Str::studly($filename);
            $last = $segmentsStudly ? end($segmentsStudly) : null;

            if ($last !== $modelName) {
                $segmentsStudly[] = $modelName;
            }
        }

        return [
            'namespaceParts' => $segmentsStudly,
            'modelName'      => $modelName,
            'isPivot'        => $isPivot,
            'segments'       => $segments,
            'filename'       => $filename,
        ];
    }

    private function generateModelAndField($file, array $json, $allFiles): void
    {
        $info           = $this->resolveModelInfo($file);
        $namespaceParts = $info['namespaceParts'];
        $modelName      = $info['modelName'];
        $segments       = $info['segments'];
        $filename       = $info['filename'];

        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $modelDir       = app_path('Models/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($modelDir);

        $table      = $json['model']['table'];
        $primaryKey = $json['model']['primaryKey'];
        $enabled    = $json['model']['enabled'] ? 'true' : 'false';
        $sortOrder  = $json['model']['sort_order'] ?? 1;

        $traitName  = "{$modelName}Field";
        $modelClass = "{$modelName}Model";

        // Field trait
        $fieldProps = $this->generateFieldProperties($json['fields'], $primaryKey);

        $traitContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$modelNamespace};

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait {$traitName}
{
{$fieldProps}
}
PHP;

        File::put("{$modelDir}/{$traitName}.php", $traitContent);

        // Relations + allowedRelations + imports
        $relations        = $this->generateRelationshipMethods($file, $json, $info, $allFiles);
        $importBlock      = $relations['imports'];
        $relationMethods  = $relations['methods'];
        $allowedRelations = $relations['allowed'];

        if (empty($allowedRelations)) {
            $allowedRelationsCode = '[]';
        } else {
            $allowedRelationsCode = "[\n        '" . implode("',\n        '", $allowedRelations) . "',\n    ]";
        }

        // Module attributes
        $moduleUsage     = "#[ModuleUsage(enabled: {$enabled}, sort_order: {$sortOrder})]";
        $moduleOperation = $this->generateOperationAttribute($json['operations'] ?? [], $segments, $filename);

        // Model class
        $modelContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$modelNamespace};

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use {$modelNamespace}\\{$traitName};

{$importBlock}
{$moduleUsage}
{$moduleOperation}
class {$modelClass} extends BaseModel
{
    use {$traitName};

    public \$table = '{$table}';
    public \$primaryKey = '{$primaryKey}';
    public string \$defaultSorting = '-{$primaryKey}';

    public array \$allowedRelations = {$allowedRelationsCode};

{$relationMethods}
}
PHP;

        File::put("{$modelDir}/{$modelClass}.php", $modelContent);
        $this->info("📘 Model generated: {$modelClass}");
    }
    private function generateRelationshipMethods($file, array $json, array $info, $allFiles): array
    {
        $imports = [];
        $methods = [];
        $allowed = [];

        $primaryKey   = $json['model']['primaryKey'];
        $baseFilename = $info['filename'];
        $baseSegments = $info['segments'];

        /* ---------------------------------------------------------
         * HasOne ilişkiler (fields[].form.relationship)
         * --------------------------------------------------------- */
        foreach ($json['fields'] as $fieldName => $meta) {
            $form = $meta['form'] ?? [];
            $rel  = $form['relationship'] ?? null;

            if (
                !$rel ||
                empty($rel['type']) ||
                empty($rel['route']) ||
                empty($rel['fields']['id'])
            ) {
                continue;
            }

            $methodName = Str::camel($rel['type']);
            $allowed[]  = $methodName;

            $relatedFqn = $this->resolveRelationshipModel($rel['route']);
            if (!$relatedFqn) {
                continue;
            }

            $short = class_basename($relatedFqn);

            $imports[] = "use {$relatedFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasOne;";

            $foreignKey = $rel['fields']['id']; // related table column
            $localKey   = $fieldName;           // this model column

            $methods[] = <<<PHP
    public function {$methodName}(): HasOne
    {
        return \$this->hasOne({$short}::class, '{$foreignKey}', '{$localKey}');
    }
PHP;
        }

        /* ---------------------------------------------------------
         * Pivot HasMany (product_image, product_translation, ...)
         * --------------------------------------------------------- */
        foreach ($allFiles as $pivotFile) {
            $pivotFilename = pathinfo($pivotFile->getFilename(), PATHINFO_FILENAME);

            // sadece product_* gibi pivotlar
            if (!Str::startsWith($pivotFilename, $baseFilename . '_')) {
                continue;
            }

            $pivotInfo = $this->resolveModelInfo($pivotFile);

            // aynı modül dizini olmalı
            if ($pivotInfo['segments'] !== $baseSegments) {
                continue;
            }

            // child slug: image, translation, video...
            $childSlug = Str::after($pivotFilename, $baseFilename . '_');
            if ($childSlug === '') {
                continue;
            }

            $pivotNamespaceParts = $pivotInfo['namespaceParts'];
            $pivotModelName      = $pivotInfo['modelName'];
            $pivotFqn            = 'App\\Models\\' . implode('\\', $pivotNamespaceParts) . '\\' . $pivotModelName . 'Model';
            $pivotShort          = $pivotModelName . 'Model';

            $imports[] = "use {$pivotFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasMany;";

            $methodName = Str::camel(Str::plural($childSlug)); // images, translations, videos...
            $allowed[]  = $methodName;

            $methods[] = <<<PHP
    public function {$methodName}(): HasMany
    {
        return \$this->hasMany({$pivotShort}::class, '{$primaryKey}', '{$primaryKey}');
    }
PHP;
        }

        $imports = array_unique($imports);
        $allowed = array_values(array_unique($allowed));

        $importBlock = '';
        if (!empty($imports)) {
            $importBlock = implode("\n", $imports) . "\n";
        }

        $methodsBlock = '';
        if (!empty($methods)) {
            $methodsBlock = implode("\n\n", $methods) . "\n";
        }

        return [
            'imports' => $importBlock,
            'methods' => $methodsBlock,
            'allowed' => $allowed,
        ];
    }

    /* ========================================================================
     * REQUEST GENERATION
     * ====================================================================== */
    private function generateRequests($file, array $json): void
    {
        $info           = $this->resolveModelInfo($file);
        $namespaceParts = $info['namespaceParts'];
        $modelName      = $info['modelName'];

        $requestDir       = app_path('Http/Requests/' . implode('/', $namespaceParts));
        $requestNamespace = 'App\\Http\\Requests\\' . implode('\\', $namespaceParts);
        File::ensureDirectoryExists($requestDir);

        $fields     = $json['fields'] ?? [];
        $actionsMap = [];

        foreach ($fields as $fieldName => $meta) {
            $actions = $meta['actions'] ?? [];
            foreach ($actions as $action) {
                $actionsMap[$action][$fieldName] = $meta;
            }
        }

        $baseMap = [
            'index'   => 'BaseIndexRequest',
            'show'    => 'BaseShowRequest',
            'store'   => 'BaseStoreRequest',
            'update'  => 'BaseUpdateRequest',
            'destroy' => 'BaseDestroyRequest',
        ];

        foreach ($actionsMap as $action => $fieldsByAction) {
            $baseClassName = $baseMap[$action] ?? 'FormRequest';

            if ($baseClassName === 'FormRequest') {
                $useBase = "use Illuminate\\Foundation\\Http\\FormRequest;";
                $extends = "FormRequest";
                $merge   = false;
            } else {
                $useBase = "use App\\Http\\Requests\\{$baseClassName};";
                $extends = $baseClassName;
                $merge   = true;
            }

            $rulesLines    = [];
            $messagesLines = [];

            foreach ($fieldsByAction as $field => $meta) {
                $form       = $meta['form'] ?? [];
                $required   = $form['required'] ?? false;
                $validations = $meta['validations'] ?? [];

                if ($required) {
                    if (!in_array('required', $validations, true)) {
                        array_unshift($validations, 'required');
                    }
                    $validations = array_values(array_filter($validations, fn($r) => $r !== 'nullable'));
                } else {
                    if (!in_array('nullable', $validations, true)) {
                        array_unshift($validations, 'nullable');
                    }
                    $validations = array_values(array_filter($validations, fn($r) => $r !== 'required'));
                }

                $rulesLines[] =
                    "            '{$field}' => ['" . implode("', '", $validations) . "'],";

                foreach ($validations as $rule) {
                    $ruleKey = Str::before($rule, ':');
                    $label   = Str::title(str_replace('_', ' ', $field));
                    $messagesLines[] =
                        "            '{$field}.{$ruleKey}' => '{$label} alanı için {$ruleKey} kuralı geçersizdir.',";
                }
            }

            $rulesBlock    = implode("\n", $rulesLines);
            $messagesBlock = implode("\n", $messagesLines);

            if ($merge) {
                $rulesMethod = <<<PHP
    public function rules(): array
    {
        return array_merge(parent::rules(), [
{$rulesBlock}
        ]);
    }
PHP;
                $messagesMethod = <<<PHP
    public function messages(): array
    {
        return array_merge(parent::messages(), [
{$messagesBlock}
        ]);
    }
PHP;
            } else {
                $rulesMethod = <<<PHP
    public function rules(): array
    {
        return [
{$rulesBlock}
        ];
    }
PHP;
                $messagesMethod = <<<PHP
    public function messages(): array
    {
        return [
{$messagesBlock}
        ];
    }
PHP;
            }

            $requestClass = "{$modelName}" . Str::studly($action) . "Request";
            $filePath     = "{$requestDir}/{$requestClass}.php";

            $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$requestNamespace};

{$useBase}

class {$requestClass} extends {$extends}
{
{$rulesMethod}

{$messagesMethod}
}
PHP;

            File::put($filePath, $content);
            $this->info("🧾 Request generated: {$requestClass}");
        }
    }
    /* ========================================================================
     * FIELD TRAIT + HELPERS
     * ====================================================================== */
    private function generateFieldProperties(array $fields, string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            $form     = $meta['form'] ?? [];
            $table    = $meta['table'] ?? ['showing', 'hiding', 'filtering', 'sorting'];
            $database = $meta['database'] ?? [];

            // FormField
            $formFieldAttr = $this->buildFormFieldAttribute($meta);

            // TableColumn
            $tableList = $this->simpleList($table);
            if ($name === $primaryKey) {
                $sorting   = "['{$primaryKey}' => 'desc']";
                $tableAttr = "#[TableColumn({$tableList}, {$sorting}, primaryKey: '{$primaryKey}')]";
            } else {
                $tableAttr = "#[TableColumn({$tableList})]";
            }

            // ActionType (JSON'daki actions birebir)
            $actions    = $meta['actions'] ?? [];
            $actionList = $this->simpleList($actions);
            $actionAttr = "#[ActionType({$actionList})]";

            // PHP type + nullable
            $phpType  = $this->phpType($form['type'] ?? 'text');
            $nullable = !empty($database['nullable']) ? '?' : '';

            $lines[] =
                "    {$formFieldAttr}\n" .
                "    {$tableAttr}\n" .
                "    {$actionAttr}\n" .
                "    protected {$nullable}{$phpType} \${$name};";
        }

        return implode("\n\n", $lines);
    }

    private function buildFormFieldAttribute(array $meta): string
    {
        $form = $meta['form'] ?? [];

        $type      = $form['type'] ?? 'text';
        $required  = $form['required'] ?? false;
        $sortOrder = $form['sort_order'] ?? 0;
        $default   = $form['default'] ?? null;
        $value     = $form['value'] ?? null;
        $relationship = $form['relationship'] ?? [];
        $options      = $form['options'] ?? [];

        $parts = [];
        $parts[] = "type: '{$type}'";
        $parts[] = $required ? "required: true" : "required: false";

        if ($default !== null && $default !== '') {
            $parts[] = "default: '{$default}'";
        }

        if ($value !== null && $value !== '') {
            $parts[] = "value: '{$value}'";
        }

        if (!empty($relationship)) {
            $rel = var_export($relationship, true);
            $rel = str_replace(["array (", ")"], ["[", "]"], $rel);
            $rel = preg_replace('/\s+/', ' ', $rel);
            $parts[] = "relationship: {$rel}";
        }

        if (!empty($options)) {
            $opt = var_export($options, true);
            $opt = str_replace(["array (", ")"], ["[", "]"], $opt);
            $opt = preg_replace('/\s+/', ' ', $opt);
            $parts[] = "options: {$opt}";
        }

        $parts[] = "sort_order: {$sortOrder}";

        return "#[FormField(" . implode(', ', $parts) . ")]";
    }

    private function phpType(string $formType): string
    {
        return match ($formType) {
            'number'  => 'int',
            'boolean' => 'bool',
            default   => 'string',
        };
    }

    private function simpleList(array $arr): string
    {
        if (empty($arr)) {
            return '[]';
        }

        $vals = array_map(fn($v) => "'" . addslashes((string) $v) . "'", $arr);
        return '[' . implode(', ', $vals) . ']';
    }

    private function resolveRelationshipModel(string $route): ?string
    {
        if ($route === '') {
            return null;
        }

        $parts  = explode('/', $route);
        $studly = array_map(fn($p) => Str::studly($p), $parts);

        if (empty($studly)) {
            return null;
        }

        $namespace = 'App\\Models\\' . implode('\\', $studly);
        $className = end($studly) . 'Model';

        return $namespace . '\\' . $className;
    }

    private function generateOperationAttribute(array $operations, array $segments, string $filename): string
    {
        if (empty($operations)) {
            return '';
        }

        $prefix = strtolower(implode('.', $segments));

        $items = [];
        foreach ($operations as $code => $op) {
            $plural   = !empty($op['plural']) ? 'true' : 'false';
            $singular = !empty($op['singular']) ? 'true' : 'false';
            $sort     = $op['sort_order'] ?? 0;

            $lastSegment = end($segments);
            if ($lastSegment && $lastSegment === $filename) {
                $routeName = "{$prefix}.{$code}";
            } else {
                $namePart  = Str::of($filename)->replace('_', '.')->lower();
                $routeName = "{$prefix}.{$namePart}.{$code}";
            }

            $items[] =
                "        ['code' => '{$code}', 'plural' => {$plural}, 'singular' => {$singular}, 'route_name' => '{$routeName}', 'sort_order' => {$sort}],";
        }

        $itemsBlock = implode("\n", $items);

        return "#[ModuleOperation(\n    items: [\n{$itemsBlock}\n    ]\n)]";
    }

    private function generateMigration($file, array $json, array $info, string $timestamp): void
    {
        $table      = $json['model']['table'];
        $primaryKey = $json['model']['primaryKey'];
        $fields     = $json['fields'];

        $className = 'Create' . Str::studly($table) . 'Table';
        $path      = database_path("migrations/{$timestamp}_create_{$table}_table.php");

        $columnsCode = $this->buildMigrationColumns($fields, $primaryKey);
        $foreignKeys = $this->buildMigrationForeignKeys($fields);

        $content = <<<PHP
<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('{$table}', function (Blueprint \$table) {
{$columnsCode}

{$foreignKeys}
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$table}');
    }
};
PHP;

        File::put($path, $content);
        $this->info("📦 Migration generated: {$className}");
    }

    private function buildMigrationColumns(array $fields, string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            $db = $meta['database'] ?? [];
            $type = $db['variable'] ?? 'string';
            $nullable = $db['nullable'] ?? false;
            $unique = $db['unique'] ?? false;
            $default = $db['default'] ?? null;

            // Primary key
            if ($name === $primaryKey && $type === 'bigIncrements') {
                $lines[] = "            \$table->bigIncrements('{$name}');";
                continue;
            }

            // TYPE
            switch ($type) {
                case 'bigIncrements':
                    $lines[] = "            \$table->bigIncrements('{$name}');";
                    continue 2;

                case 'unsignedBigInteger':
                    $col = "\$table->unsignedBigInteger('{$name}')";
                    break;

                case 'integer':
                    $col = "\$table->integer('{$name}')";
                    break;

                case 'boolean':
                    $col = "\$table->boolean('{$name}')";
                    break;

                case 'decimal':
                    $col = "\$table->decimal('{$name}', 15, 2)";
                    break;

                case 'timestamp':
                    $col = "\$table->timestamp('{$name}')";
                    break;

                default:
                    $col = "\$table->{$type}('{$name}')";
                    break;
            }

            // NULLABLE
            if ($nullable) {
                $col .= "->nullable()";
            }

            // UNIQUE
            if ($unique) {
                $col .= "->unique()";
            }

            // DEFAULT
            if ($default !== null) {
                $defaultValue = is_bool($default) ? ($default ? 'true' : 'false') : "'{$default}'";
                $col .= "->default({$defaultValue})";
            }

            $col .= ";";
            $lines[] = "            {$col}";
        }

        return implode("\n", $lines);
    }

    private function buildMigrationForeignKeys(array $fields): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            $valid = $meta['validations'] ?? [];

            foreach ($valid as $rule) {
                if (!str_starts_with($rule, 'exists:')) {
                    continue;
                }

                [$table, $col] = explode(',', str_replace('exists:', '', $rule));

                $lines[] =
                    "            \$table->foreign('{$name}')
                ->references('{$col}')
                ->on('{$table}')
                ->nullOnDelete();";
            }
        }

        return implode("\n\n", $lines);
    }
}
