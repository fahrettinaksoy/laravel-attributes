<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StructureModel extends Command
{
    protected $signature = 'structure:model {--Requests} {--Migration} {--Factory} {--Seeder}';

    protected $description = 'Generate Models, Field traits, Relation Models and optionally FormRequests, Migrations, Factory and Seeders from /structure JSON files.';

    private array $generatedSeeders = [];

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

            $hasFactory = false;
            if ($this->option('Factory')) {
                $this->generateFactory($file, $json, $this->resolveModelInfo($file));
                $hasFactory = true;
            }

            if ($this->option('Seeder')) {
                $seederInfo = $this->generateSeeder($file, $json, $this->resolveModelInfo($file), $hasFactory);
                if ($seederInfo) {
                    $this->generatedSeeders[] = $seederInfo;
                }
            }
        }

        if ($this->option('Seeder') && !empty($this->generatedSeeders)) {
            $this->updateDatabaseSeeder();
        }

        $this->info("\n✅ structure:model completed — Models, Fields, Relations, Requests, Migrations, Factory and Seeders generated.\n");
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
            if (!$relatedFqn) continue;

            $short = class_basename($relatedFqn);

            $imports[] = "use {$relatedFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasOne;";

            $foreignKey = $rel['fields']['id'];

            $methods[] =
                "    public function {$methodName}(): HasOne\n    {\n".
                "        return \$this->hasOne({$short}::class, '{$foreignKey}', '{$fieldName}');\n".
                "    }";
        }

        foreach ($allFiles as $pivotFile) {
            $filename = pathinfo($pivotFile->getFilename(), PATHINFO_FILENAME);

            if (!Str::startsWith($filename, $baseFilename . '_')) {
                continue;
            }
            if (substr_count($filename, '_') !== 1) {
                continue;
            }

            $pivotInfo = $this->resolveModelInfo($pivotFile);
            if ($pivotInfo['segments'] !== $baseSegments) {
                continue;
            }

            $childSlug = Str::after($filename, $baseFilename . '_');
            if ($childSlug === '') continue;

            $pivotFqn = 'App\\Models\\' .
                implode('\\', $pivotInfo['namespaceParts']) .
                '\\' .
                $pivotInfo['modelName'] . 'Model';

            $pivotShort = $pivotInfo['modelName'] . 'Model';

            $imports[] = "use {$pivotFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasMany;";

            $methodName = Str::camel(Str::plural($childSlug));

            $allowed[] = $methodName;

            $methods[] =
                "    public function {$methodName}(): HasMany\n    {\n".
                "        return \$this->hasMany({$pivotShort}::class, '{$primaryKey}', '{$primaryKey}');\n".
                "    }";
        }

        foreach ($allFiles as $childFile) {
            $childName = pathinfo($childFile->getFilename(), PATHINFO_FILENAME);

            if (!Str::startsWith($childName, $baseFilename . '_')) continue;
            if (substr_count($childName, '_') !== 2) continue;

            if ($info['depth'] !== 1) continue;

            $childSlug = Str::after($childName, $baseFilename . '_');
            if ($childSlug === '') continue;

            $childInfo = $this->resolveModelInfo($childFile);

            $childFqn = 'App\\Models\\' .
                implode('\\', $childInfo['namespaceParts']) .
                '\\' .
                $childInfo['modelName'] . 'Model';

            $childShort = $childInfo['modelName'] . 'Model';

            $imports[] = "use {$childFqn};";
            $imports[] = "use Illuminate\\Database\\Eloquent\\Relations\\HasMany;";

            $methodName = Str::camel(Str::plural($childSlug));

            $allowed[] = $methodName;

            $methods[] =
                "    public function {$methodName}(): HasMany\n    {\n".
                "        return \$this->hasMany({$childShort}::class, '{$primaryKey}', '{$primaryKey}');\n".
                "    }";
        }

        return [
            'imports' => empty($imports = array_unique($imports)) ? '' : implode("\n", $imports) . "\n",
            'methods' => empty($methods) ? '' : implode("\n\n", $methods) . "\n",
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

    private function generateFactory($file, array $json, array $info): void
    {
        $namespaceParts = $info['namespaceParts'];
        $modelName = $info['modelName'];
        $modelClass = "{$modelName}Model";

        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $factoryNamespace = 'Database\\Factories\\' . implode('\\', $namespaceParts);

        $factoryDir = database_path('factories/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($factoryDir);

        $factoryClass = "{$modelClass}Factory";
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
        $isUnique = $db['unique'] ?? false;
        $maxLength = $db['length'] ?? null;

        $lower = strtolower($name);

        if (str_contains($lower, 'uuid')) {
            return '$this->faker->uuid()';
        }

        if (str_contains($lower, 'slug')) {
            return '$this->faker->unique()->slug()';
        }

        if (str_contains($lower, 'sku')) {
            return '$this->faker->unique()->bothify("SKU-####-????")';
        }

        if (str_contains($lower, 'code')) {
            return $isUnique
                ? '$this->faker->unique()->bothify("CODE-####")'
                : '$this->faker->bothify("CODE-####")';
        }

        if (str_contains($lower, 'email')) {
            return '$this->faker->unique()->safeEmail()';
        }

        if (str_contains($lower, 'phone')) {
            return $isUnique
                ? '$this->faker->unique()->phoneNumber()'
                : '$this->faker->phoneNumber()';
        }

        if (str_contains($lower, 'rating') || str_contains($lower, 'rate') || str_contains($lower, 'score')) {
            return '$this->faker->numberBetween(1, 5)';
        }

        if (str_contains($lower, 'quantity') || str_contains($lower, 'stock') || str_contains($lower, 'count')) {
            return '$this->faker->numberBetween(0, 1000)';
        }

        if (str_contains($lower, 'order') || str_contains($lower, 'sort') || str_contains($lower, 'position')) {
            return '$this->faker->numberBetween(1, 100)';
        }

        if (str_contains($lower, 'year')) {
            return '$this->faker->year()';
        }

        if (str_contains($lower, 'age')) {
            return '$this->faker->numberBetween(18, 80)';
        }

        if (str_contains($lower, 'name')) {
            return '$this->faker->words(3, true)';
        }

        if (str_contains($lower, 'author')) {
            return '$this->faker->name()';
        }

        if (str_contains($lower, 'title')) {
            return '$this->faker->sentence(4)';
        }

        if (str_contains($lower, 'summary')) {
            return '$this->faker->sentence(10)';
        }

        if (str_contains($lower, 'description')) {
            if ($type === 'string') {
                return '$this->faker->realText(200)';
            }
            return '$this->faker->paragraph(2)';
        }

        if (str_contains($lower, 'content') || str_contains($lower, 'body')) {
            if ($type === 'string') {
                return '$this->faker->realText(200)';
            }
            return '$this->faker->paragraphs(3, true)';
        }

        if (str_contains($lower, 'address')) {
            return '$this->faker->address()';
        }

        if (str_contains($lower, 'url') || str_contains($lower, 'link') || str_contains($lower, 'website')) {
            return '$this->faker->url()';
        }

        if (str_contains($lower, 'image') || str_contains($lower, 'photo') || str_contains($lower, 'picture')) {
            return '$this->faker->imageUrl(640, 480)';
        }

        if (str_contains($lower, 'currency')) {
            return '$this->faker->currencyCode()';
        }

        if (str_contains($lower, 'country')) {
            return '$this->faker->country()';
        }

        if (str_contains($lower, 'city')) {
            return '$this->faker->city()';
        }

        if (preg_match('/_id$/', $name)) {
            return '$this->faker->numberBetween(1, 100)';
        }

        if (str_contains($lower, 'price') || str_contains($lower, 'amount') || str_contains($lower, 'cost')) {
            return '$this->faker->randomFloat(2, 10, 9999)';
        }

        if (str_contains($lower, 'date')) {
            return '$this->faker->dateTime()';
        }

        if (str_contains($lower, 'status')) {
            return '$this->faker->boolean()';
        }

        if (str_contains($lower, 'percent') || str_contains($lower, 'rate')) {
            return '$this->faker->numberBetween(0, 100)';
        }

        switch ($type) {
            case 'boolean':
                return '$this->faker->boolean()';
            case 'integer':
            case 'unsignedInteger':
            case 'unsignedBigInteger':
            case 'tinyInteger':
            case 'smallInteger':
            case 'mediumInteger':
                return '$this->faker->numberBetween(1, 9999)';
            case 'decimal':
            case 'float':
            case 'double':
                return '$this->faker->randomFloat(2, 1, 9999)';
            case 'timestamp':
            case 'datetime':
            case 'date':
                return '$this->faker->dateTime()';
            case 'time':
                return '$this->faker->time()';
            case 'text':
            case 'mediumText':
            case 'longText':
                if (in_array($formType, ['textarea', 'editor'], true)) {
                    return '$this->faker->paragraphs(2, true)';
                }
                return '$this->faker->paragraph()';
            case 'json':
                return "json_encode(['key' => \$this->faker->word()])";
            case 'string':
            default:
                if ($isUnique) {
                    return '$this->faker->unique()->word()';
                }

                if ($maxLength && $maxLength <= 50) {
                    return '$this->faker->word()';
                } elseif ($maxLength && $maxLength <= 100) {
                    return '$this->faker->sentence(3)';
                } elseif ($maxLength && $maxLength <= 255) {
                    return '$this->faker->sentence(10)';
                }

                if (in_array($formType, ['textarea', 'editor'], true)) {
                    return '$this->faker->sentence(10)';
                }

                return '$this->faker->word()';
        }
    }

    private function generateSeeder($file, array $json, array $info, bool $hasFactory): ?array
    {
        $namespaceParts = $info['namespaceParts'];
        $modelName = $info['modelName'];
        $modelClass = "{$modelName}Model";
        $table = $json['model']['table'];

        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $seederNamespace = 'Database\\Seeders\\' . implode('\\', $namespaceParts);

        $seederDir = database_path('seeders/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($seederDir);

        $seederClass = "{$modelClass}Seeder";

        $isRelation = $info['isRelation'];
        $depth = $info['depth'];
        $primaryKey = $json['model']['primaryKey'];

        if ($hasFactory) {
            if ($isRelation && $depth === 1) {
                $parentModelName = $this->getParentModelFromFilename($info['filename']);
                $parentClass = Str::studly($parentModelName) . 'Model';
                $parentNamespace = 'App\\Models\\' . implode('\\', array_slice($namespaceParts, 0, -2));
                $foreignKey = Str::snake($parentModelName) . '_id';
                $parentPrimaryKey = Str::snake($parentModelName) . '_id';
                $runMethod = "    public function run(): void\n    {\n        // Önce tabloyu temizle\n        {$modelClass}::query()->delete();\n\n        // Parent modelden kayıtları al\n        \$parents = \\{$parentNamespace}\\{$parentClass}::all();\n\n        // Her parent için 1-5 arası ilişkili kayıt oluştur\n        foreach (\$parents as \$parent) {\n            {$modelClass}::factory()\n                ->count(rand(1, 5))\n                ->create(['{$foreignKey}' => \$parent->{$parentPrimaryKey}]);\n        }\n    }";
            } elseif ($isRelation && $depth === 2) {
                $parts = explode('_', $info['filename']);
                $parentFilename = $parts[0] . '_' . $parts[1];
                $parentClass = Str::studly($parentFilename) . 'Model';
                $parentNamespaceParts = array_slice($namespaceParts, 0, -2);
                $parentNamespace = 'App\\Models\\' . implode('\\', $parentNamespaceParts);
                $foreignKey = Str::snake($parentFilename) . '_id';
                $parentPrimaryKey = Str::snake($parentFilename) . '_id';

                $runMethod = "    public function run(): void\n    {\n        // Önce tabloyu temizle\n        {$modelClass}::query()->delete();\n\n        // Parent modelden kayıtları al\n        \$parents = \\{$parentNamespace}\\{$parentClass}::all();\n\n        // Her parent için 1-3 arası ilişkili kayıt oluştur\n        foreach (\$parents as \$parent) {\n            {$modelClass}::factory()\n                ->count(rand(1, 3))\n                ->create(['{$foreignKey}' => \$parent->{$parentPrimaryKey}]);\n        }\n    }";
            } else {
                $runMethod = "    public function run(): void\n    {\n        // Önce tabloyu temizle\n        {$modelClass}::query()->delete();\n\n        {$modelClass}::factory()\n            ->count(rand(10, 50))\n            ->create();\n    }";
            }
        } else {
            $insertData = $this->buildSeederInsertData($json['fields'] ?? [], $primaryKey, 10);

            $runMethod = "    public function run(): void\n    {\n        // Önce tabloyu temizle\n        {$modelClass}::query()->delete();\n\n        \$data = [\n{$insertData}\n        ];\n\n        foreach (\$data as \$item) {\n            {$modelClass}::create(\$item);\n        }\n    }";
        }

        File::put(
            "{$seederDir}/{$seederClass}.php",
            "<?php\n\ndeclare(strict_types=1);\n\nnamespace {$seederNamespace};\n\nuse {$modelNamespace}\\{$modelClass};\nuse Illuminate\\Database\\Seeder;\n\nclass {$seederClass} extends Seeder\n{\n{$runMethod}\n}\n"
        );

        return [
            'class' => $seederClass,
            'namespace' => $seederNamespace,
            'fqn' => "{$seederNamespace}\\{$seederClass}",
            'depth' => $depth,
        ];
    }

    private function getParentModelFromFilename(string $filename): string
    {
        $parts = explode('_', $filename);
        return $parts[0];
    }

    private function updateDatabaseSeeder(): void
    {
        $databaseSeederPath = database_path('seeders/DatabaseSeeder.php');

        if (!File::exists($databaseSeederPath)) {
            $this->createDatabaseSeeder();
            return;
        }

        usort($this->generatedSeeders, fn($a, $b) => ($a['depth'] ?? 0) <=> ($b['depth'] ?? 0));

        $content = File::get($databaseSeederPath);

        preg_match_all('/^use\s+(.+);$/m', $content, $existingUses);
        $existingUseFqns = $existingUses[1] ?? [];

        preg_match_all('/\$this->call\((.+?)::class\);/s', $content, $existingCalls);
        $existingCallClasses = array_map(function($call) {
            return trim(str_replace(['$this->call(', '::class);'], '', $call));
        }, $existingCalls[0] ?? []);

        $newUses = [];
        $newCalls = [];

        foreach ($this->generatedSeeders as $seederInfo) {
            $fqn = $seederInfo['fqn'];
            $class = $seederInfo['class'];

            if (!in_array($fqn, $existingUseFqns)) {
                $newUses[] = "use {$fqn};";
            }

            if (!in_array($class, $existingCallClasses)) {
                $newCalls[] = "            \$this->call({$class}::class);";
            }
        }

        if (empty($newUses) && empty($newCalls)) {
            $this->info("DatabaseSeeder already up to date.");
            return;
        }

        if (!empty($newUses)) {
            $lastUsePos = strrpos($content, 'use ');
            if ($lastUsePos !== false) {
                $endOfLinePos = strpos($content, "\n", $lastUsePos);
                $content = substr_replace(
                    $content,
                    "\n" . implode("\n", $newUses),
                    $endOfLinePos,
                    0
                );
            } else {
                $namespacePos = strpos($content, 'namespace');
                if ($namespacePos !== false) {
                    $endOfLinePos = strpos($content, "\n", $namespacePos);
                    $content = substr_replace(
                        $content,
                        "\n\n" . implode("\n", $newUses),
                        $endOfLinePos,
                        0
                    );
                }
            }
        }

        if (!empty($newCalls)) {
            if (preg_match('/try\s*\{([^}]+)\}/s', $content, $tryBlock)) {
                $lastCallPos = strrpos($tryBlock[1], '$this->call(');
                if ($lastCallPos !== false) {
                    $tryContent = $tryBlock[1];
                    $endOfLinePos = strpos($tryContent, "\n", $lastCallPos);
                    $newTryContent = substr_replace(
                        $tryContent,
                        "\n" . implode("\n", $newCalls),
                        $endOfLinePos,
                        0
                    );
                    $content = str_replace($tryBlock[1], $newTryContent, $content);
                }
            }
        }

        File::put($databaseSeederPath, $content);
        $this->info("✅ DatabaseSeeder.php updated with new seeders.");
    }

    private function createDatabaseSeeder(): void
    {
        $uses = [];
        $calls = [];

        foreach ($this->generatedSeeders as $seederInfo) {
            $uses[] = "use {$seederInfo['fqn']};";
            $calls[] = "            \$this->call({$seederInfo['class']}::class);";
        }

        $content = "<?php\n\nnamespace Database\\Seeders;\n\n";
        $content .= implode("\n", $uses);
        $content .= "\n\nuse Illuminate\\Database\\Seeder;\n";
        $content .= "use Illuminate\\Support\\Facades\\DB;\n\n";
        $content .= "class DatabaseSeeder extends Seeder\n{\n";
        $content .= "    public function run(): void\n    {\n";
        $content .= "        DB::statement('SET FOREIGN_KEY_CHECKS=0;');\n\n";
        $content .= "        try {\n";
        $content .= "            // Tüm tabloları temizle\n";
        $content .= "            \$this->command->info('Truncating tables...');\n";
        $content .= "            DB::table('cat_product')->truncate();\n";
        $content .= "            // Diğer tablolar da eklenebilir\n\n";
        $content .= implode("\n", $calls);
        $content .= "\n        } finally {\n";
        $content .= "            DB::statement('SET FOREIGN_KEY_CHECKS=1;');\n";
        $content .= "        }\n";
        $content .= "    }\n";
        $content .= "}\n";

        File::put(database_path('seeders/DatabaseSeeder.php'), $content);
        $this->info("✅ DatabaseSeeder.php created with all seeders.");
    }

    private function buildSeederInsertData(array $fields, ?string $primaryKey, int $count): string
    {
        $records = [];

        for ($i = 1; $i <= $count; $i++) {
            $record = [];

            foreach ($fields as $name => $meta) {
                if ($name === $primaryKey) continue;
                if (in_array($name, ['created_at', 'updated_at', 'created_by', 'updated_by'], true)) continue;

                $value = $this->generateRandomValue($name, $meta, $i);
                $record[] = "                '{$name}' => {$value}";
            }

            $records[] = "            [\n" . implode(",\n", $record) . ",\n            ]";
        }

        return implode(",\n", $records);
    }

    private function generateRandomValue(string $name, array $meta, int $index): string
    {
        $db = $meta['database'] ?? [];
        $type = $db['variable'] ?? 'string';
        $formType = $meta['form']['type'] ?? null;

        $lower = strtolower($name);

        if (str_contains($lower, 'email')) {
            return "'user{$index}@example.com'";
        }

        if (str_contains($lower, 'phone')) {
            return "'+90" . rand(5000000000, 5999999999) . "'";
        }

        if (str_contains($lower, 'name') || str_contains($lower, 'title')) {
            return "'{$name} " . $index . "'";
        }

        if (preg_match('/_id$/', $name)) {
            return (string)rand(1, 100);
        }

        if (str_contains($lower, 'price') || str_contains($lower, 'amount')) {
            return (string)rand(100, 10000);
        }

        switch ($type) {
            case 'boolean':
                return rand(0, 1) ? 'true' : 'false';
            case 'integer':
            case 'unsignedInteger':
            case 'unsignedBigInteger':
                return (string)rand(1, 1000);
            case 'decimal':
                return "'" . number_format(rand(100, 100000) / 100, 2, '.', '') . "'";
            case 'timestamp':
            case 'datetime':
                return "now()";
            default:
                if (in_array($formType, ['textarea', 'editor'], true)) {
                    return "'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sample text for {$name}.'";
                }
                return "'{$name} value {$index}'";
        }
    }
}
