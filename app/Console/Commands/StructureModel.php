<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StructureModel extends Command
{
    protected $signature = 'structure:model {--Requests} {--Migration} {--Factory}';

    protected $description = 'Generate Models, Field traits, Relation Models and optionally FormRequests, Migrations and Factory from /structure JSON files.';

    public function handle(): void
    {
        $timestamp = date('Y_m_d_His');
        $jsonFiles = File::allFiles(base_path('structure'));

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
                $this->generateMigration($file, $json, $this->resolveModelInfo($file), $timestamp);
            }

            if ($this->option('Factory')) {
                $this->generateFactory($file, $json, $this->resolveModelInfo($file));
            }
        }

        $this->info("\n✅ structure:model completed — Models, Fields, Relations, Requests, Migrations and Factory generated.\n");
    }

    private function resolveModelInfo($file): array
    {
        $relative = str_replace(['\\', '/'], '/', $file->getRelativePath());
        $segments = array_filter(explode('/', $relative));
        $segmentsStudly = array_map(fn($s) => Str::studly($s), $segments);
        $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $underscoreCount = substr_count($filename, '_');

        if ($underscoreCount === 0) {
            $modelName = Str::studly($filename);
            if (empty($segmentsStudly) || end($segmentsStudly) !== $modelName) {
                $segmentsStudly[] = $modelName;
            }

            return [
                'namespaceParts' => $segmentsStudly,
                'modelName'      => $modelName,
                'isRelation'     => false,
                'segments'       => $segments,
                'filename'       => $filename,
                'depth'          => 0,
            ];
        }

        $parts = explode('_', $filename);
        $depth = count($parts) - 1;
        $firstPart = Str::studly($parts[0]);

        if (empty($segmentsStudly) || end($segmentsStudly) !== $firstPart) {
            $segmentsStudly[] = $firstPart;
        }
        $segmentsStudly[] = 'Relations';

        if ($depth === 1) {
            $segmentsStudly[] = Str::studly($filename);
        } else {
            for ($i = 1; $i < count($parts); $i++) {
                $segmentsStudly[] = Str::studly(implode('_', array_slice($parts, 0, $i + 1)));
                if ($i < count($parts) - 1) {
                    $segmentsStudly[] = 'Relations';
                }
            }
        }

        return [
            'namespaceParts' => $segmentsStudly,
            'modelName'      => Str::studly($filename),
            'isRelation'     => true,
            'segments'       => $segments,
            'filename'       => $filename,
            'depth'          => $depth,
        ];
    }

    private function generateModelAndField($file, array $json, $allFiles): void
    {
        $info = $this->resolveModelInfo($file);
        $namespaceParts = $info['namespaceParts'];
        $modelName = $info['modelName'];
        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $modelDir = app_path('Models/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($modelDir);

        $table = $json['model']['table'];
        $primaryKey = $json['model']['primaryKey'];
        $enabled = $json['model']['enabled'] ? 'true' : 'false';
        $sortOrder = $json['model']['sort_order'] ?? 1;
        $traitName = "{$modelName}Field";
        $modelClass = "{$modelName}Model";

        $fieldProps = $this->generateFieldProperties($json['fields'], $primaryKey);
        File::put(
            "{$modelDir}/{$traitName}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$modelNamespace};\n\nuse App\Attributes\Model\FormField;\nuse App\Attributes\Model\TableColumn;\nuse App\Attributes\Model\ActionType;\n\ntrait {$traitName}\n{\n{$fieldProps}\n}\n"
        );

        $relations = $this->generateRelationshipMethods($file, $json, $info, $allFiles);
        $allowedRelationsCode = empty($relations['allowed'])
            ? '[]'
            : "[\n        '" . implode("',\n        '", $relations['allowed']) . "',\n    ]";

        $moduleUsage = "#[ModuleUsage(enabled: {$enabled}, sort_order: {$sortOrder})]";
        $moduleOperation = $this->generateOperationAttribute($json['operations'] ?? [], $info['segments'], $info['filename']);

        File::put(
            "{$modelDir}/{$modelClass}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$modelNamespace};\n\nuse App\Models\BaseModel;\nuse App\Attributes\Model\ModuleUsage;\nuse App\Attributes\Model\ModuleOperation;\nuse {$modelNamespace}\\{$traitName};\n\n{$relations['imports']}{$moduleUsage}\n{$moduleOperation}\nclass {$modelClass} extends BaseModel\n{\n    use {$traitName};\n\n    public \$table = '{$table}';\n    public \$primaryKey = '{$primaryKey}';\n    public string \$defaultSorting = '-{$primaryKey}';\n\n    public array \$allowedRelations = {$allowedRelationsCode};\n\n{$relations['methods']}}\n"
        );
    }

    private function generateRelationshipMethods($file, array $json, array $info, $allFiles): array
    {
        $imports = [];
        $methods = [];
        $allowed = [];

        $primaryKey = $json['model']['primaryKey'];
        $baseFilename = $info['filename'];
        $baseSegments = $info['segments'];

        foreach ($json['fields'] as $fieldName => $meta) {
            $rel = ($meta['form'] ?? [])['relationship'] ?? null;
            if (!$rel || empty($rel['type']) || empty($rel['route']) || empty($rel['fields']['id'])) {
                continue;
            }

            $methodName = Str::camel($rel['type']);
            $allowed[] = $methodName;
            $relatedFqn = $this->resolveRelationshipModel($rel['route']);
            if (!$relatedFqn) {
                continue;
            }

            $short = class_basename($relatedFqn);
            $imports[] = "use {$relatedFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasOne;";
            $foreignKey = $rel['fields']['id'];
            $methods[] =
                "    public function {$methodName}(): HasOne\n    {\n        return \$this->hasOne({$short}::class, '{$foreignKey}', '{$fieldName}');\n    }";
        }

        foreach ($allFiles as $pivotFile) {
            $pivotFilename = pathinfo($pivotFile->getFilename(), PATHINFO_FILENAME);

            if (!Str::startsWith($pivotFilename, $baseFilename . '_')) {
                continue;
            }

            $pivotInfo = $this->resolveModelInfo($pivotFile);
            if ($pivotInfo['segments'] !== $baseSegments) {
                continue;
            }

            $childSlug = Str::after($pivotFilename, $baseFilename . '_');
            if ($childSlug === '') {
                continue;
            }

            $pivotFqn = 'App\\Models\\' . implode('\\', $pivotInfo['namespaceParts']) . '\\' . $pivotInfo['modelName'] . 'Model';
            $pivotShort = $pivotInfo['modelName'] . 'Model';
            $imports[] = "use {$pivotFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasMany;";
            $methodName = Str::camel(Str::plural($childSlug));
            $allowed[] = $methodName;

            $methods[] =
                "    public function {$methodName}(): HasMany\n    {\n        return \$this->hasMany({$pivotShort}::class, '{$primaryKey}', '{$primaryKey}');\n    }";
        }

        return [
            'imports' => empty($imports = array_unique($imports))
                ? ''
                : implode("\n", $imports) . "\n",
            'methods' => empty($methods)
                ? ''
                : implode("\n\n", $methods) . "\n",
            'allowed' => array_values(array_unique($allowed)),
        ];
    }

    private function generateRequests($file, array $json): void
    {
        $info = $this->resolveModelInfo($file);
        $requestDir = app_path('Http/Requests/' . implode('/', $info['namespaceParts']));
        $requestNamespace = 'App\\Http\\Requests\\' . implode('\\', $info['namespaceParts']);
        File::ensureDirectoryExists($requestDir);

        $actionsMap = [];
        foreach ($json['fields'] ?? [] as $fieldName => $meta) {
            foreach ($meta['actions'] ?? [] as $action) {
                $actionsMap[$action][$fieldName] = $meta;
            }
        }

        $baseMap = [
            'index' => 'BaseIndexRequest',
            'show' => 'BaseShowRequest',
            'store' => 'BaseStoreRequest',
            'update' => 'BaseUpdateRequest',
            'destroy' => 'BaseDestroyRequest',
        ];

        foreach ($actionsMap as $action => $fieldsByAction) {
            $baseClassName = $baseMap[$action] ?? 'FormRequest';
            $useBase =
                $baseClassName === 'FormRequest'
                    ? "use Illuminate\\Foundation\\Http\\FormRequest;"
                    : "use App\\Http\\Requests\\{$baseClassName};";
            $extends = $baseClassName === 'FormRequest' ? 'FormRequest' : $baseClassName;

            $hasAnyValidation = false;

            foreach ($fieldsByAction as $meta) {
                if (!empty($meta['validations'][$action] ?? [])) {
                    $hasAnyValidation = true;
                    break;
                }
            }

            if (!$hasAnyValidation) {
                $rulesMethod =
                    "    public function rules(): array\n    {\n        return array_merge(parent::rules(), []);\n    }";
                $messagesMethod =
                    "    public function messages(): array\n    {\n        return array_merge(parent::messages(), []);\n    }";
            } else {
                $rulesLines = [];
                $messagesLines = [];

                foreach ($fieldsByAction as $field => $meta) {
                    $validations = array_values(
                        array_filter($meta['validations'][$action] ?? [], fn($r) => is_string($r))
                    );

                    if (empty($validations)) {
                        continue;
                    }

                    $rulesLines[] =
                        "            '{$field}' => ['" .
                        implode("', '", $validations) .
                        "'],";

                    foreach ($validations as $rule) {
                        $ruleKey = Str::before($rule, ':');
                        $label = Str::title(str_replace('_', ' ', $field));
                        $messagesLines[] =
                            "            '{$field}.{$ruleKey}' => '{$label} alanı için {$ruleKey} kuralı geçersizdir.',";
                    }
                }

                $rulesMethod =
                    "    public function rules(): array\n    {\n        return array_merge(parent::rules(), [\n" .
                    implode("\n", $rulesLines) .
                    "\n        ]);\n    }";

                $messagesMethod =
                    "    public function messages(): array\n    {\n        return array_merge(parent::messages(), [\n" .
                    implode("\n", $messagesLines) .
                    "\n        ]);\n    }";
            }

            $requestClass = "{$info['modelName']}" . Str::studly($action) . "Request";
            $content =
                "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$requestNamespace};\n\n{$useBase}\n\nclass {$requestClass} extends {$extends}\n{\n{$rulesMethod}\n\n{$messagesMethod}\n}\n";

            File::put("{$requestDir}/{$requestClass}.php", $content);
        }
    }

    private function generateFieldProperties(array $fields, string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            $formFieldAttr = $this->buildFormFieldAttribute($meta);
            $table = $meta['table'] ?? ['showing', 'hiding', 'filtering', 'sorting'];
            $tableList = $this->simpleList($table);

            $tableAttr =
                $name === $primaryKey
                    ? "#[TableColumn({$tableList}, ['{$primaryKey}' => 'desc'], primaryKey: '{$primaryKey}')]"
                    : "#[TableColumn({$tableList})]";

            $actionAttr =
                '#[ActionType(' .
                $this->simpleList($meta['actions'] ?? []) .
                ')]';

            $phpType = match (($meta['form'] ?? [])['type'] ?? 'text') {
                'number' => 'int',
                'boolean' => 'bool',
                default => 'string'
            };

            $nullable = !empty($meta['database']['nullable']) ? '?' : '';

            $lines[] =
                "    {$formFieldAttr}\n    {$tableAttr}\n    {$actionAttr}\n    protected {$nullable}{$phpType} \${$name};";
        }

        return implode("\n\n", $lines);
    }

    private function buildFormFieldAttribute(array $meta): string
    {
        $form = $meta['form'] ?? [];
        $parts = [
            "type: '" . ($form['type'] ?? 'text') . "'",
            ($form['required'] ?? false)
                ? 'required: true'
                : 'required: false',
        ];

        if (($form['default'] ?? null) !== null && $form['default'] !== '') {
            $parts[] = "default: '{$form['default']}'";
        }

        if (($form['value'] ?? null) !== null && $form['value'] !== '') {
            $parts[] = "value: '{$form['value']}'";
        }

        if (!empty($form['relationship'])) {
            $rel = preg_replace(
                '/\s+/',
                ' ',
                str_replace(['array (', ')'], ['[', ']'], var_export($form['relationship'], true))
            );
            $parts[] = "relationship: {$rel}";
        }

        if (!empty($form['options'])) {
            $opt = preg_replace(
                '/\s+/',
                ' ',
                str_replace(['array (', ')'], ['[', ']'], var_export($form['options'], true))
            );
            $parts[] = "options: {$opt}";
        }

        $parts[] = "sort_order: " . ($form['sort_order'] ?? 0);

        return "#[FormField(" . implode(', ', $parts) . ")]";
    }

    private function simpleList(array $arr): string
    {
        return empty($arr)
            ? '[]'
            : '[' .
            implode(
                ', ',
                array_map(fn($v) => "'" . addslashes((string) $v) . "'", $arr)
            ) .
            ']';
    }

    private function resolveRelationshipModel(string $route): ?string
    {
        if ($route === '') {
            return null;
        }

        $studly = array_map(fn($p) => Str::studly($p), explode('/', $route));

        return empty($studly)
            ? null
            : 'App\\Models\\' .
            implode('\\', $studly) .
            '\\' .
            end($studly) .
            'Model';
    }

    private function generateOperationAttribute(array $operations, array $segments, string $filename): string
    {
        if (empty($operations)) {
            return '';
        }

        $prefix = strtolower(implode('.', $segments));
        $items = [];

        foreach ($operations as $code => $op) {
            $plural = !empty($op['plural']) ? 'true' : 'false';
            $singular = !empty($op['singular']) ? 'true' : 'false';
            $lastSegment = end($segments);

            $routeName =
                $lastSegment && $lastSegment === $filename
                    ? "{$prefix}.{$code}"
                    : "{$prefix}." .
                    Str::of($filename)
                        ->replace('_', '.')
                        ->lower() .
                    ".{$code}";

            $items[] =
                "        ['code' => '{$code}', 'plural' => {$plural}, 'singular' => {$singular}, 'route_name' => '{$routeName}', 'sort_order' => " .
                ($op['sort_order'] ?? 0) .
                "],";
        }

        return "#[ModuleOperation(\n    items: [\n" .
            implode("\n", $items) .
            "\n    ]\n)]";
    }

    private function generateMigration($file, array $json, array $info, string $timestamp): void
    {
        $table = $json['model']['table'];
        $columnsCode = $this->buildMigrationColumns($json['fields'], $json['model']['primaryKey']);

        File::put(
            database_path("migrations/{$timestamp}_create_{$table}_table.php"),
            "<?php\n\nuse Illuminate\\Database\\Migrations\\Migration;\nuse Illuminate\\Database\\Schema\\Blueprint;\nuse Illuminate\\Support\\Facades\\Schema;\n\nreturn new class extends Migration {\n    public function up(): void\n    {\n        Schema::create('{$table}', function (Blueprint \$table) {\n{$columnsCode}\n        });\n    }\n\n    public function down(): void\n    {\n        Schema::dropIfExists('{$table}');\n    }\n};\n"
        );
    }

    private function buildMigrationColumns(array $fields, string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            if ($name === 'created_at') {
                $lines[] =
                    "            \$table->timestamp('created_at')->useCurrent();";
                continue;
            }

            if ($name === 'updated_at') {
                $lines[] =
                    "            \$table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();";
                continue;
            }

            $db = $meta['database'] ?? [];
            $type = $db['variable'] ?? 'string';

            if ($name === $primaryKey && $type === 'bigIncrements') {
                $lines[] =
                    "            \$table->bigIncrements('{$name}');";
                continue;
            }

            $col = match ($type) {
                'bigIncrements' => "\$table->bigIncrements('{$name}')",
                'unsignedBigInteger' => "\$table->unsignedBigInteger('{$name}')",
                'integer' => "\$table->integer('{$name}')",
                'boolean' => "\$table->boolean('{$name}')",
                'decimal' => "\$table->decimal('{$name}', 15, 2)",
                'timestamp' => "\$table->timestamp('{$name}')",
                default => "\$table->{$type}('{$name}')"
            };

            if ($type === 'bigIncrements') {
                $lines[] = "            {$col};";
                continue;
            }

            if ($db['nullable'] ?? false) {
                $col .= '->nullable()';
            }

            if ($db['unique'] ?? false) {
                $col .= '->unique()';
            }

            if (($default = $db['default'] ?? null) !== null) {
                $defaultValue = is_bool($default)
                    ? ($default ? 'true' : 'false')
                    : ($default === 'CURRENT_TIMESTAMP'
                        ? "DB::raw('CURRENT_TIMESTAMP')"
                        : "'{$default}'");
                $col .= "->default({$defaultValue})";
            }

            $lines[] = "            {$col};";
        }

        return implode("\n", $lines);
    }

    /**
     * --Factory → Database\Factories altına Factory oluşturur
     */
    private function generateFactory($file, array $json, array $info): void
    {
        $namespaceParts = $info['namespaceParts'];
        $modelName = $info['modelName'];
        $modelClass = "{$modelName}Model";

        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $factoryNamespace = 'Database\\Factories\\' . implode('\\', $namespaceParts);

        $factoryDir = database_path('factories/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($factoryDir);

        $factoryClass = "{$modelName}Factory";
        $primaryKey = $json['model']['primaryKey'] ?? null;
        $fieldsCode = $this->buildFactoryDefinitionArray($json['fields'] ?? [], $primaryKey);

        File::put(
            "{$factoryDir}/{$factoryClass}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$factoryNamespace};\n\nuse {$modelNamespace}\\{$modelClass};\nuse Illuminate\\Database\\Eloquent\\Factories\\Factory;\n\n/**\n * @extends Factory<{$modelClass}>\n */\nclass {$factoryClass} extends Factory\n{\n    protected \$model = {$modelClass}::class;\n\n    public function definition(): array\n    {\n        return [\n{$fieldsCode}\n        ];\n    }\n}\n"
        );
    }

    private function buildFactoryDefinitionArray(array $fields, ?string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            if ($name === $primaryKey) continue;
            if (in_array($name, [
                'created_at',
                'updated_at',
                'created_by',
                'updated_by'
            ], true)) continue;

            $value = $this->guessFactoryValue($name, $meta);
            $lines[] = "            '{$name}' => {$value},";
        }

        return implode("\n", $lines);
    }

    private function guessFactoryValue(string $name, array $meta): string
    {
        $db = $meta['database'] ?? [];
        $type = $db['variable'] ?? 'string';
        $formType = $meta['form']['type'] ?? null;

        $lower = strtolower($name);

        if (str_contains($lower, 'uuid')) return '$this->faker->uuid()';
        if (str_contains($lower, 'slug')) return '$this->faker->slug()';
        if (str_contains($lower, 'email')) return '$this->faker->safeEmail()';
        if (str_contains($lower, 'phone')) return '$this->faker->phoneNumber()';
        if (str_contains($lower, 'name')) return '$this->faker->words(3, true)';
        if (preg_match('/_id$/', $name)) return '$this->faker->numberBetween(1, 9999)';
        if (str_contains($lower, 'price')) return '$this->faker->randomFloat(2, 10, 9999)';
        if (str_contains($lower, 'date')) return '$this->faker->dateTime()';
        if (str_contains($lower, 'status')) return '$this->faker->boolean()';

        switch ($type) {
            case 'boolean':
                return '$this->faker->boolean()';
            case 'integer':
            case 'unsignedInteger':
            case 'unsignedBigInteger':
                return '$this->faker->numberBetween(1, 9999)';
            case 'decimal':
                return '$this->faker->randomFloat(2, 1, 9999)';
            case 'timestamp':
            case 'datetime':
                return '$this->faker->dateTime()';
            default:
                if (in_array($formType, ['textarea', 'editor'], true)) {
                    return '$this->faker->paragraph()';
                }
                return '$this->faker->word()';
        }
    }
}
