<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModelsFromMigration extends Command
{
    protected $signature = 'make:models-from-migration {migration : Path to the migration file}';
    protected $description = 'Generate BaseModel-based models with Field traits and automatic hasMany() relations.';

    private array $hasManyRelations = [];
    private array $allTables = [];
    private string $migrationContent = '';

    public function handle(): void
    {
        $migrationPath = base_path($this->argument('migration'));
        if (!File::exists($migrationPath)) {
            $this->error("❌ Migration file not found: {$migrationPath}");
            return;
        }

        $this->migrationContent = File::get($migrationPath);
        preg_match_all("/private const ([A-Z0-9_]+) = '([^']+)'/", $this->migrationContent, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            $this->warn('⚠️ No tables found in migration.');
            return;
        }

        // Tüm tabloları kaydet
        foreach ($matches as $match) {
            $this->allTables[] = $match[2];
        }

        $this->info("📋 Found tables: " . implode(', ', $this->allTables));

        // Step 1: Analyze table structure and find foreign key relations
        foreach ($matches as $match) {
            $table = $match[2];
            $this->analyzeTableRelations($table);
        }

        // Debug: İlişkileri göster
        if (!empty($this->hasManyRelations)) {
            $this->info("🔍 Detected relations:");
            foreach ($this->hasManyRelations as $parent => $children) {
                $this->info("  {$parent} has " . count($children) . " children");
                foreach ($children as $child) {
                    $this->info("    - {$child['childTable']} via {$child['foreignKey']}");
                }
            }
        }

        // Step 2: Create all models with Field traits
        foreach ($matches as $match) {
            $table = $match[2];
            $this->createModel($table);
        }

        // Step 3: Inject hasMany relations into parent models
        $this->injectRelationsIntoModels();

        $this->info('🎉 All models and relations generated successfully!');
    }

    private function analyzeTableRelations(string $table): void
    {
        $pattern = "/Schema::create\([^,]+,\s*function\s*\([^)]*\)\s*\{(.*?)\n\s*\}\);/s";

        preg_match_all($pattern, $this->migrationContent, $allSchemas, PREG_SET_ORDER);

        $schemaContent = null;
        foreach ($allSchemas as $schema) {
            if (strpos($schema[0], "'{$table}'") !== false ||
                strpos($schema[0], "TABLE_" . strtoupper(str_replace('-', '_', $table))) !== false) {
                $schemaContent = $schema[1];
                break;
            }
        }

        if (!$schemaContent) {
            return;
        }

        // Foreign key kolonlarını bul
        preg_match_all("/\\\$table->unsignedBigInteger\('([a-z_]+_id)'\)/", $schemaContent, $foreignKeys);

        if (empty($foreignKeys[1])) {
            return;
        }

        foreach ($foreignKeys[1] as $foreignKey) {
            $parentTableSuffix = Str::beforeLast($foreignKey, '_id');
            $parentFullTable = $this->findParentTable($parentTableSuffix, $table);

            if ($parentFullTable) {
                $this->hasManyRelations[$parentFullTable][] = [
                    'childTable' => $table,
                    'foreignKey' => $foreignKey,
                ];
            }
        }
    }

    private function findParentTable(string $parentSuffix, string $childTable): ?string
    {
        $parts = explode('_', $childTable);
        $prefix = $parts[0];

        $possibleTables = [
            "{$prefix}_{$parentSuffix}",
            "def_{$prefix}_{$parentSuffix}",
        ];

        foreach ($possibleTables as $possibleTable) {
            if (in_array($possibleTable, $this->allTables)) {
                return $possibleTable;
            }
        }

        return null;
    }

    private function createModel(string $table): void
    {
        $info = $this->parseTable($table);

        File::ensureDirectoryExists($info['dir']);

        // Field trait dosyasını oluştur
        $this->createFieldTraitFile($info, $table);

        // Model dosyasını oluştur
        $this->createModelFile($info);
    }

    private function parseTable(string $table): array
    {
        // Tablo adını parçalara ayır
        $parts = explode('_', $table);

        // Alt model mi ana model mi kontrol et
        $isChildModel = $this->isChildModel($table);

        if ($isChildModel) {
            // Alt model: cat_product_image
            $prefix = array_shift($parts); // cat
            $parentPart = array_shift($parts); // product
            $childPart = implode('_', $parts); // image

            $parentModelName = Str::studly(Str::singular($parentPart)); // Product
            $childModelName = Str::studly(Str::singular($childPart)); // Image

            // Model adı: ProductImageModel
            $modelName = $parentModelName . $childModelName . 'Model';

            // Field trait: ProductImageField
            $fieldTrait = $parentModelName . $childModelName . 'Field';

            // Dizin adı: ProductImage
            $dirName = $parentModelName . $childModelName;

            // Namespace: App\Models\Cat\Product\Pivots\ProductImage
            $namespace = Str::studly($prefix) . '\\' . $parentModelName . '\\Pivots\\' . $dirName;

            // Dizin: app/Models/Cat/Product/Pivots/ProductImage
            $dir = app_path('Models/' . Str::studly($prefix) . '/' . $parentModelName . '/Pivots/' . $dirName);

            // Primary key: image_id
            $primaryKey = Str::snake(Str::singular($childPart)) . '_id';

        } else {
            // Ana model: cat_product, def_cat_language, def_cat_category

            if (count($parts) === 2) {
                // cat_product -> Cat\Product
                $prefix = array_shift($parts);
                $modelPart = array_shift($parts);

                $namespace = Str::studly($prefix) . '\\' . Str::studly($modelPart);
                $dir = app_path('Models/' . Str::studly($prefix) . '/' . Str::studly($modelPart));

            } elseif (count($parts) === 3) {
                // def_cat_language -> Def\Cat\Language
                $prefix = array_shift($parts);
                $middlePart = array_shift($parts);
                $modelPart = array_shift($parts);

                $namespace = Str::studly($prefix) . '\\' . Str::studly($middlePart) . '\\' . Str::studly($modelPart);
                $dir = app_path('Models/' . Str::studly($prefix) . '/' . Str::studly($middlePart) . '/' . Str::studly($modelPart));

            } else {
                // Fallback
                $modelPart = array_pop($parts);
                $namespace = implode('\\', array_map(fn($p) => Str::studly($p), $parts)) . '\\' . Str::studly($modelPart);
                $dir = app_path('Models/' . implode('/', array_map(fn($p) => Str::studly($p), $parts)) . '/' . Str::studly($modelPart));
            }

            $modelName = Str::studly(Str::singular($modelPart)) . 'Model';
            $fieldTrait = Str::studly(Str::singular($modelPart)) . 'Field';
            $primaryKey = Str::snake(Str::singular($modelPart)) . '_id';
        }

        return [
            'namespace' => $namespace,
            'dir' => $dir,
            'modelName' => $modelName,
            'primaryKey' => $primaryKey,
            'fieldTrait' => $fieldTrait,
            'table' => $table,
        ];
    }

    private function isChildModel(string $table): bool
    {
        $parts = explode('_', $table);

        // En az 3 parça olmalı
        if (count($parts) < 3) {
            return false;
        }

        // İlk iki parçadan parent tablo adını oluştur
        $prefix = $parts[0];
        $possibleParent = $parts[1];

        $parentTable = "{$prefix}_{$possibleParent}";

        // Parent tablo var mı?
        if (!in_array($parentTable, $this->allTables)) {
            return false;
        }

        // Bu tabloda parent'a foreign key var mı?
        $expectedForeignKey = Str::snake($possibleParent) . '_id';

        // Schema'dan kontrol et
        $pattern = "/Schema::create\([^,]+,\s*function\s*\([^)]*\)\s*\{(.*?)\n\s*\}\);/s";
        preg_match_all($pattern, $this->migrationContent, $allSchemas, PREG_SET_ORDER);

        foreach ($allSchemas as $schema) {
            if (strpos($schema[0], "'{$table}'") !== false) {
                $schemaContent = $schema[1];
                if (strpos($schemaContent, "'{$expectedForeignKey}'") !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function createFieldTraitFile(array $info, string $table): void
    {
        $file = "{$info['dir']}/{$info['fieldTrait']}.php";

        if (File::exists($file)) {
            $this->warn("⚠️ Field trait already exists: {$file}");
            return;
        }

        // Tablo şemasını parse et (SIRAYLA!)
        $fields = $this->extractFieldsFromSchema($table);

        if (empty($fields)) {
            $this->warn("⚠️ No fields extracted for: {$table}");
            return;
        }

        // Field properties oluştur
        $fieldProperties = '';
        $sortOrder = 0;

        foreach ($fields as $field) {
            $attributes = $this->generateFieldAttributes($field, $table, $sortOrder);
            $fieldProperties .= $attributes;
            $fieldProperties .= "    protected {$field['type']} \${$field['name']};\n\n";

            $sortOrder++; // Her field için sıra numarasını artır
        }

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Models\\{$info['namespace']};

use App\\Attributes\\Model\\ActionType;
use App\\Attributes\\Model\\FormField;
use App\\Attributes\\Model\\TableColumn;

trait {$info['fieldTrait']}
{
{$fieldProperties}}
PHP;

        File::put($file, $code);
        $this->info("✅ Created Field trait: {$file}");
    }

    private function generateFieldAttributes(array $field, string $table, int $sortOrder): string
    {
        $attributes = '';
        $columnName = $field['name'];
        $columnType = $field['type'];
        $dbType = $field['dbType'] ?? 'string';
        $isPrimaryKey = $field['isPrimaryKey'] ?? false;

        // Primary Key için (product_id, category_id, etc.)
        if ($isPrimaryKey) {
            $attributes .= "    #[ActionType(['show'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'hiding'])]\n";
            $attributes .= "    #[FormField(type: 'number', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Foreign key mi kontrol et
        $isForeignKey = Str::endsWith($columnName, '_id') && !$isPrimaryKey;

        // created_by ve updated_by için özel durum
        if ($columnName === 'created_by' || $columnName === 'updated_by') {
            $attributes .= "    #[ActionType(['show'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'hiding'])]\n";
            $attributes .= "    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'system/user', 'fields' => ['id' => '{$columnName}', 'label' => 'full_name']], sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // UUID için
        if ($columnName === 'uuid') {
            $attributes .= "    #[ActionType(['show'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'hiding'])]\n";
            $attributes .= "    #[FormField(type: 'text', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // created_at, updated_at için
        if (in_array($columnName, ['created_at', 'updated_at'])) {
            $attributes .= "    #[ActionType(['show'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'datetime', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Code alanı için özel durum (store ve update yok)
        if ($columnName === 'code') {
            $attributes .= "    #[ActionType(['index', 'show'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'text', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Foreign key için
        if ($isForeignKey) {
            $parentTable = Str::beforeLast($columnName, '_id');

            // Parent route'u tahmin et
            $parts = explode('_', $table);
            $prefix = $parts[0];
            $parentRoute = strtolower($prefix) . '/' . Str::kebab($parentTable);

            $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => '{$parentRoute}', 'fields' => ['id' => '{$columnName}', 'label' => 'name']], sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Boolean alanlar için
        if ($columnType === 'bool') {
            $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'checkbox', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Text/Textarea alanlar için
        if (in_array($columnName, ['description', 'content', 'meta_description', 'notes']) || $dbType === 'text') {
            $attributes .= "    #[ActionType(['show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing'])]\n";
            $attributes .= "    #[FormField(type: 'textarea', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Numeric alanlar için
        if ($columnType === 'int' || $columnType === 'float') {
            $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'number', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Image/File alanlar için
        if (in_array($columnName, ['image', 'photo', 'avatar', 'logo', 'path', 'file'])) {
            $attributes .= "    #[ActionType(['show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing'])]\n";
            $attributes .= "    #[FormField(type: 'file', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // URL alanlar için
        if (Str::contains($columnName, ['url', 'link', 'website', 'video_url'])) {
            $attributes .= "    #[ActionType(['show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing'])]\n";
            $attributes .= "    #[FormField(type: 'url', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Email alanlar için
        if (Str::contains($columnName, 'email')) {
            $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering'])]\n";
            $attributes .= "    #[FormField(type: 'email', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Name, Title, SKU gibi önemli string alanlar
        if (in_array($columnName, ['name', 'title', 'sku'])) {
            $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
            $attributes .= "    #[TableColumn(['showing', 'filtering', 'sorting'])]\n";
            $attributes .= "    #[FormField(type: 'text', sort_order: {$sortOrder})]\n";
            return $attributes;
        }

        // Default string alanlar için
        $attributes .= "    #[ActionType(['index', 'show', 'store', 'update'])]\n";
        $attributes .= "    #[TableColumn(['showing', 'filtering'])]\n";
        $attributes .= "    #[FormField(type: 'text', sort_order: {$sortOrder})]\n";

        return $attributes;
    }

    private function getPrimaryKeyFromTable(string $table): string
    {
        $parts = explode('_', $table);
        $lastPart = end($parts);
        return Str::snake(Str::singular($lastPart)) . '_id';
    }

    private function extractFieldsFromSchema(string $table): array
    {
        $pattern = "/Schema::create\([^,]+,\s*function\s*\([^)]*\)\s*\{(.*?)\n\s*\}\);/s";

        preg_match_all($pattern, $this->migrationContent, $allSchemas, PREG_SET_ORDER);

        $schemaContent = null;
        foreach ($allSchemas as $schema) {
            if (strpos($schema[0], "'{$table}'") !== false ||
                strpos($schema[0], "TABLE_" . strtoupper(str_replace('-', '_', $table))) !== false) {
                $schemaContent = $schema[1];
                break;
            }
        }

        if (!$schemaContent) {
            return [];
        }

        $fields = [];

        // Tüm satırları al ve sırayla işle
        $lines = explode("\n", $schemaContent);

        foreach ($lines as $line) {
            $line = trim($line);

            // Column tanımlamaları
            $patterns = [
                "/\\\$table->bigIncrements\('([^']+)'\)/" => 'bigIncrements',
                "/\\\$table->uuid\('([^']+)'\)/" => 'uuid',
                "/\\\$table->string\('([^']+)'(?:,\s*(\d+))?\)/" => 'string',
                "/\\\$table->text\('([^']+)'\)/" => 'text',
                "/\\\$table->char\('([^']+)'(?:,\s*(\d+))?\)/" => 'char',
                "/\\\$table->unsignedBigInteger\('([^']+)'\)/" => 'unsignedBigInteger',
                "/\\\$table->integer\('([^']+)'\)/" => 'integer',
                "/\\\$table->unsignedInteger\('([^']+)'\)/" => 'unsignedInteger',
                "/\\\$table->decimal\('([^']+)'(?:,\s*\d+(?:,\s*\d+)?)?\)/" => 'decimal',
                "/\\\$table->boolean\('([^']+)'\)/" => 'boolean',
                "/\\\$table->timestamp\('([^']+)'\)/" => 'timestamp',
                "/\\\$table->datetime\('([^']+)'\)/" => 'datetime',
                "/\\\$table->date\('([^']+)'\)/" => 'date',
            ];

            foreach ($patterns as $pattern => $type) {
                if (preg_match($pattern, $line, $match)) {
                    $columnName = $match[1];

                    // Type mapping
                    $phpType = $this->mapColumnTypeToPhp($type, $columnName);

                    $fields[] = [
                        'name' => $columnName,
                        'type' => $phpType,
                        'dbType' => $type,
                        'isPrimaryKey' => $type === 'bigIncrements',
                    ];

                    break; // Her satırda sadece bir column tanımı var
                }
            }
        }

        return $fields;
    }

    private function mapColumnTypeToPhp(string $dbType, string $columnName): string
    {
        if ($dbType === 'boolean' ||
            Str::startsWith($columnName, 'is_') ||
            Str::startsWith($columnName, 'has_')) {
            return 'bool';
        }

        if (in_array($dbType, ['integer', 'unsignedInteger', 'unsignedBigInteger'])) {
            return 'int';
        }

        if ($dbType === 'decimal') {
            return 'float';
        }

        return 'string';
    }

    private function createModelFile(array $info): void
    {
        $file = "{$info['dir']}/{$info['modelName']}.php";

        if (File::exists($file)) {
            $this->warn("⚠️ Model already exists: {$file}");
            return;
        }

        $code = <<<PHP
<?php

declare(strict_types=1);

namespace App\\Models\\{$info['namespace']};

use App\\Attributes\\Model\\ModuleUsage;
use App\\Models\\BaseModel;
use App\\Models\\{$info['namespace']}\\{$info['fieldTrait']};

#[ModuleUsage(enabled: true, sort_order: 1)]
class {$info['modelName']} extends BaseModel
{
    use {$info['fieldTrait']};

    public \$table = '{$info['table']}';
    public \$primaryKey = '{$info['primaryKey']}';
    public string \$defaultSorting = '-{$info['primaryKey']}';
    public array \$allowedRelations = [];
}
PHP;
        File::put($file, $code);
        $this->info("✅ Created model: {$file}");
    }

    private function injectRelationsIntoModels(): void
    {
        if (empty($this->hasManyRelations)) {
            return;
        }

        $this->info("🔗 Starting relation injection...");

        foreach ($this->hasManyRelations as $parentTable => $relations) {
            $parentInfo = $this->parseTable($parentTable);
            $modelFile = "{$parentInfo['dir']}/{$parentInfo['modelName']}.php";

            if (!File::exists($modelFile)) {
                $this->error("❌ Parent model not found: {$modelFile}");
                continue;
            }

            $content = File::get($modelFile);

            // Zaten ilişki varsa atla
            if (preg_match('/public function \w+\(\).*HasMany/', $content)) {
                $this->warn("⚠️ {$parentInfo['modelName']} already has relations, skipping.");
                continue;
            }

            $injected = '';
            $allowedRelations = [];

            foreach ($relations as $rel) {
                $childInfo = $this->parseTable($rel['childTable']);

                // İlişki metod adı: cat_product_image -> images
                $lastPart = Str::afterLast($rel['childTable'], '_');
                $relationName = Str::camel(Str::plural($lastPart));

                $allowedRelations[] = "'{$relationName}'";

                $injected .= <<<PHP

    public function {$relationName}(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return \$this->hasMany(
            \App\Models\\{$childInfo['namespace']}\\{$childInfo['modelName']}::class,
            '{$rel['foreignKey']}',
            '{$parentInfo['primaryKey']}'
        );
    }

PHP;
            }

            // allowedRelations array'ini güncelle
            $allowedRelationsStr = implode(', ', $allowedRelations);
            $content = Str::replace(
                "public array \$allowedRelations = [];",
                "public array \$allowedRelations = [{$allowedRelationsStr}];",
                $content
            );

            // İlişkileri ekle
            $updated = Str::replaceLast('}', rtrim($injected) . "\n}", $content);
            File::put($modelFile, $updated);

            $this->info("✅ Added " . count($relations) . " relation(s) to {$parentInfo['modelName']}");
        }
    }
}
