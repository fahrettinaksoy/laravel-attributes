<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StructureModel extends Command
{
    protected $signature = 'structure:model {path?}';
    protected $description = 'Generate model files from JSON definitions under /structure folder with ModuleUsage and ModuleOperation attributes';

    public function handle(): void
    {
        $basePath = base_path('structure');
        $target = $this->argument('path') ? $basePath . '/' . $this->argument('path') : $basePath;
        $jsonFiles = File::allFiles($target);

        foreach ($jsonFiles as $file) {
            $json = json_decode(File::get($file->getRealPath()), true);
            if (!isset($json['model'])) {
                $this->error("❌ Skipping {$file->getRelativePathname()} — missing 'model' key");
                continue;
            }
            $this->generateModel($file, $json);
        }

        $this->info('✅ All models generated successfully.');
    }

    protected function generateModel($file, array $json): void
    {
        $model = $json['model'];
        $table = $model['table'] ?? null;
        $primaryKey = $model['primaryKey'] ?? null;
        $enabled = $model['enabled'] ?? true;
        $sortOrder = $model['sort_order'] ?? 1;

        if (!$table || !$primaryKey) {
            $this->error("⚠️ Missing table or primaryKey in {$file->getFilename()}");
            return;
        }

        // === KLASÖR / NAMESPACE YAPISI ===
        $relativePath = str_replace(['\\', '/'], '/', $file->getRelativePath());
        $segments = array_filter(explode('/', $relativePath));
        $namespaceParts = array_map(fn($s) => Str::studly($s), $segments);

        $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME);
        $isPivot = Str::contains($filename, '_');

        if ($isPivot) {
            [$parent, $child] = explode('_', $filename, 2);
            $parentStudly = Str::studly($parent);
            $childStudly = Str::studly($child);

            if (!in_array($parentStudly, $namespaceParts)) {
                $namespaceParts[] = $parentStudly;
            }

            $namespaceParts[] = 'Pivots';
            $namespaceParts[] = $parentStudly . $childStudly;
            $modelName = $parentStudly . $childStudly;
        } else {
            $modelName = Str::studly($filename);
            if (empty($namespaceParts) || end($namespaceParts) !== $modelName) {
                $namespaceParts[] = $modelName;
            }
        }

        $namespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $modelClass = "{$modelName}Model";
        $modelDir = app_path('Models/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($modelDir);
        $modelPath = "{$modelDir}/{$modelClass}.php";

        // === ATTRIBUTE OLUŞTURMA ===
        $moduleUsageAttr = "#[ModuleUsage(enabled: {$this->boolToString($enabled)}, sort_order: {$sortOrder})]";

        $moduleOperationAttr = '';
        if (!empty($json['operations']) && is_array($json['operations'])) {
            $operationItems = [];
            foreach ($json['operations'] as $code => $data) {
                $plural = $data['plural'] ? 'true' : 'false';
                $singular = $data['singular'] ? 'true' : 'false';
                $sort_order = $data['sort_order'] ?? 0;

                // Route name oluştur (örnek: catalog.product.active)
                $route = $this->buildRouteName($segments, $filename, $code);

                $operationItems[] =
                    "        ['code' => '{$code}', 'plural' => {$plural}, 'singular' => {$singular}, 'route_name' => '{$route}', 'sort_order' => {$sort_order}],";
            }

            $itemsString = "[\n" . implode("\n", $operationItems) . "\n    ]";
            $moduleOperationAttr = "#[ModuleOperation(\n    items: {$itemsString},\n)]";
        }

        // === MODEL DOSYA İÇERİĞİ ===
        $content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

{$moduleUsageAttr}
{$moduleOperationAttr}
class {$modelClass} extends BaseModel
{
    public \$table = '{$table}';
    public \$primaryKey = '{$primaryKey}';
    public string \$defaultSorting = '-{$primaryKey}';
}
PHP;

        File::put($modelPath, $content);
        $this->info("✅ Created: {$modelPath}");
    }

    private function boolToString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    private function buildRouteName(array $segments, string $filename, string $operation): string
    {
        // catalog/product/product.json → catalog.product.active
        $prefix = strtolower(implode('.', $segments));
        $name = Str::of($filename)->replace('_', '.')->lower();
        return "{$prefix}.{$name}.{$operation}";
    }
}
