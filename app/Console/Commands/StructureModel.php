<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class StructureModel extends Command
{
    protected $signature = 'structure:model {--Requests}';
    protected $description = 'Generate Models, Field traits, Pivot Models and optionally FormRequests from /structure JSON files.';

    public function handle(): void
    {
        $basePath = base_path('structure');
        $jsonFiles = File::allFiles($basePath);

        foreach ($jsonFiles as $file) {
            $json = json_decode(File::get($file->getRealPath()), true);

            if (!$json || !isset($json['model'])) {
                $this->error("⚠️ Skipped: {$file->getRelativePathname()} — missing 'model' key");
                continue;
            }

            // Model + Field
            $this->generateModelAndField($file, $json);

            // Requests
            if ($this->option('Requests')) {
                $this->generateRequests($file, $json);
            }
        }

        $this->info("\n✅ structure:model completed — Models, Fields and Requests generated.\n");
    }

    /* ========================================================================
     *  COMMON PATH RESOLUTION (MODEL + REQUEST İÇİN AYNI)
     * ====================================================================== */
    /**
     * @return array{namespaceParts: array<int,string>, modelName: string, isPivot: bool, segments: array<int,string>, filename: string}
     */
    private function resolveModelInfo($file): array
    {
        $relative = str_replace(['\\', '/'], '/', $file->getRelativePath());
        $segments = array_filter(explode('/', $relative));              // e.g. ['catalog','product']
        $segmentsStudly = array_map(fn($s) => Str::studly($s), $segments); // ['Catalog','Product']

        $filename = pathinfo($file->getFilename(), PATHINFO_FILENAME); // e.g. product, product_image
        $isPivot = Str::contains($filename, '_');

        if ($isPivot) {
            [$parent, $child] = explode('_', $filename, 2);            // product + image
            $parentStudly = Str::studly($parent);                      // Product
            $childStudly = Str::studly($child);                        // Image

            // Son klasör parent ile aynı değilse ekle
            if (empty($segmentsStudly) || end($segmentsStudly) !== $parentStudly) {
                $segmentsStudly[] = $parentStudly;
            }

            $segmentsStudly[] = 'Pivots';
            $segmentsStudly[] = $parentStudly . $childStudly;          // ProductImage
            $modelName = $parentStudly . $childStudly;
        } else {
            $modelName = Str::studly($filename);                       // Product
            $last = $segmentsStudly ? end($segmentsStudly) : null;

            // Eğer dizin sonu zaten Product ise bir daha ekleme (çift klasör engeli)
            if ($last !== $modelName) {
                $segmentsStudly[] = $modelName;
            }
        }

        return [
            'namespaceParts' => $segmentsStudly,       // Models/Catalog/Product/... veya Pivots/...
            'modelName'      => $modelName,
            'isPivot'        => $isPivot,
            'segments'       => $segments,            // orijinal küçük harfli path ['catalog','product']
            'filename'       => $filename,
        ];
    }

    /* ========================================================================
     *  MODEL + FIELD
     * ====================================================================== */
    private function generateModelAndField($file, array $json): void
    {
        $info          = $this->resolveModelInfo($file);
        $namespaceParts = $info['namespaceParts'];
        $modelName     = $info['modelName'];
        $segments      = $info['segments'];
        $filename      = $info['filename'];

        $modelNamespace = 'App\\Models\\' . implode('\\', $namespaceParts);
        $modelDir       = app_path('Models/' . implode('/', $namespaceParts));
        File::ensureDirectoryExists($modelDir);

        $table      = $json['model']['table'];
        $primaryKey = $json['model']['primaryKey'];
        $enabled    = $json['model']['enabled'] ? 'true' : 'false';
        $sortOrder  = $json['model']['sort_order'] ?? 1;

        $traitName  = "{$modelName}Field";
        $modelClass = "{$modelName}Model";

        // ---------- FIELD TRAIT ----------
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

        // ---------- MODULE ATTRIBUTES ----------
        $moduleUsage = "#[ModuleUsage(enabled: {$enabled}, sort_order: {$sortOrder})]";
        $moduleOperation = $this->generateOperationAttribute($json['operations'] ?? [], $segments, $filename);

        // ---------- MODEL CLASS ----------
        $modelContent = <<<PHP
<?php

declare(strict_types=1);

namespace {$modelNamespace};

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use {$modelNamespace}\\{$traitName};

{$moduleUsage}
{$moduleOperation}
class {$modelClass} extends BaseModel
{
    use {$traitName};

    public \$table = '{$table}';
    public \$primaryKey = '{$primaryKey}';
    public string \$defaultSorting = '-{$primaryKey}';
}
PHP;

        File::put("{$modelDir}/{$modelClass}.php", $modelContent);
        $this->info("📘 Model generated: {$modelClass}");
    }

    /* ========================================================================
     *  REQUESTS
     * ====================================================================== */
    private function generateRequests($file, array $json): void
    {
        /* ===============================================================
           PATH ÇÖZÜMLEME (Model ile aynı dizin kültürü)
        ============================================================== */
        $info           = $this->resolveModelInfo($file);
        $namespaceParts = $info['namespaceParts'];   // Catalog/Product/Product veya Pivots/ProductImage
        $modelName      = $info['modelName'];

        // Requests için aynı klasör, sadece prefix farklı
        $requestDir       = app_path('Http/Requests/' . implode('/', $namespaceParts));
        $requestNamespace = 'App\\Http\\Requests\\' . implode('\\', $namespaceParts);
        File::ensureDirectoryExists($requestDir);

        /* ===============================================================
           FIELD → ACTION HARİTASI
        ============================================================== */
        $fields = $json['fields'] ?? [];
        $actionsMap = [];

        foreach ($fields as $fieldName => $meta) {
            $actions = $meta['actions'] ?? [];
            foreach ($actions as $action) {
                $actionsMap[$action][$fieldName] = $meta;
            }
        }

        /* ===============================================================
           BİLİNEN ACTION → BaseRequest sınıfları
        ============================================================== */
        $baseMap = [
            'index'   => 'BaseIndexRequest',
            'show'    => 'BaseShowRequest',
            'store'   => 'BaseStoreRequest',
            'update'  => 'BaseUpdateRequest',
            'destroy' => 'BaseDestroyRequest',
        ];

        /* ===============================================================
           HER ACTION İÇİN REQUEST DOSYASI OLUŞTUR
        ============================================================== */
        foreach ($actionsMap as $action => $fieldsByAction) {

            $baseClassName = $baseMap[$action] ?? 'FormRequest';

            if ($baseClassName === 'FormRequest') {
                // Bilinmeyen action (copy, close vs.)
                $useBase = "use Illuminate\Foundation\Http\FormRequest;";
                $extends = "FormRequest";
                $merge   = false;

            } else {
                // Bilinen action → BaseXRequest extends
                // BaseXRequest dosyaları App\Http\Requests altında (Base klasörü yok)
                $useBase = "use App\\Http\\Requests\\{$baseClassName};";
                $extends = $baseClassName;
                $merge   = true;
            }

            /* ===============================================================
               RULES & MESSAGES ÜRETİMİ
               required/nullable mantığı:
                 - form.required === true → required
                 - yok veya false → nullable
            ============================================================== */
            $rulesLines = [];
            $messagesLines = [];

            foreach ($fieldsByAction as $field => $meta) {
                $form = $meta['form'] ?? [];
                $required = $form['required'] ?? false;

                // validations JSON'dan gelen kurallar
                $validations = $meta['validations'] ?? [];

                if ($required) {
                    // required varsa nullable asla olmayacak
                    if (!in_array('required', $validations)) {
                        array_unshift($validations, 'required');
                    }
                    // nullable varsa sil
                    $validations = array_values(array_filter($validations, fn($r) => $r !== 'nullable'));
                } else {
                    // required yoksa nullable EKLE
                    if (!in_array('nullable', $validations)) {
                        array_unshift($validations, 'nullable');
                    }
                    // required varsa sil
                    $validations = array_values(array_filter($validations, fn($r) => $r !== 'required'));
                }

                // RULE satırı ekle
                $rulesLines[] =
                    "            '{$field}' => ['" . implode("', '", $validations) . "'],";

                // MESSAGE satırları
                foreach ($validations as $rule) {
                    $ruleKey = Str::before($rule, ':');
                    $label = Str::title(str_replace('_', ' ', $field));
                    $messagesLines[] =
                        "            '{$field}.{$ruleKey}' => '{$label} alanı için {$ruleKey} kuralı geçersizdir.',";
                }
            }

            $rulesBlock = implode("\n", $rulesLines);
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

            /* ===============================================================
               REQUEST DOSYASI YAZ
            ============================================================== */
            $requestClass = "{$modelName}" . Str::studly($action) . "Request";
            $filePath = "{$requestDir}/{$requestClass}.php";

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
     *  FIELD TRAIT ATTRIBUTE GENERATION
     * ====================================================================== */
    private function generateFieldProperties(array $fields, string $primaryKey): string
    {
        $lines = [];

        foreach ($fields as $name => $meta) {
            $form      = $meta['form'] ?? [];
            $table     = $meta['table'] ?? ['showing', 'hiding', 'filtering', 'sorting'];
            $database  = $meta['database'] ?? [];

            $type      = $form['type'] ?? 'text';
            $sortOrder = $form['sort_order'] ?? 0;
            $required  = $form['required'] ?? false;

            $relationship = isset($form['relationship'])
                ? $this->inlineArray($form['relationship'])
                : '[]';

            $options = isset($form['options'])
                ? $this->inlineArray($form['options'])
                : '[]';

            // ---- FormField params (gereksiz default'lar yok) ----
            $params = ["type: '{$type}'"];
            if ($relationship !== '[]') {
                $params[] = "relationship: {$relationship}";
            }
            if ($options !== '[]') {
                $params[] = "options: {$options}";
            }
            if ($required) {
                $params[] = "required: true";
            }
            if ($sortOrder) {
                $params[] = "sort_order: {$sortOrder}";
            }
            $paramStr = implode(', ', $params);

            // ---- TableColumn (her alanda) ----
            $tableList = $this->simpleList($table);
            if ($name === $primaryKey) {
                $sorting   = "['{$primaryKey}' => 'desc']";
                $tableAttr = "#[TableColumn({$tableList}, {$sorting}, primaryKey: '{$primaryKey}')]";
            } else {
                $tableAttr = "#[TableColumn({$tableList})]";
            }

            // ---- ActionType: JSON'daki actions birebir ----
            $actions = $meta['actions'] ?? [];
            $actionList = empty($actions) ? '[]' : $this->simpleList($actions);
            $actionAttr = "#[ActionType({$actionList})]";

            // ---- PHP type + nullable (database.nullable) ----
            $phpType  = $this->phpType($type);
            $nullable = !empty($database['nullable']) ? '?' : '';

            $lines[] =
                "    #[FormField({$paramStr})]\n" .
                "    {$tableAttr}\n" .
                "    {$actionAttr}\n" .
                "    protected {$nullable}{$phpType} \${$name};";
        }

        return implode("\n\n", $lines);
    }

    /* ========================================================================
     *  HELPERS
     * ====================================================================== */

    private function phpType(string $formType): string
    {
        return match ($formType) {
            'number'  => 'int',
            'boolean' => 'bool',
            default   => 'string',
        };
    }

    private function inlineArray(array $arr): string
    {
        if (empty($arr)) {
            return '[]';
        }

        $out = var_export($arr, true);
        $out = str_replace(["array (", ")"], ["[", "]"], $out);
        $out = preg_replace('/\s+/', ' ', $out);

        return $out;
    }

    private function simpleList(array $arr): string
    {
        if (empty($arr)) {
            return '[]';
        }

        $vals = array_map(fn($v) => "'" . addslashes((string) $v) . "'", $arr);

        return '[' . implode(', ', $vals) . ']';
    }

    private function generateOperationAttribute(array $operations, array $segments, string $filename): string
    {
        if (empty($operations)) {
            return '';
        }

        $prefix = strtolower(implode('.', $segments)); // e.g. "catalog.product"

        $items = [];
        foreach ($operations as $code => $op) {
            $plural   = !empty($op['plural'])   ? 'true' : 'false';
            $singular = !empty($op['singular']) ? 'true' : 'false';
            $sort     = $op['sort_order'] ?? 0;

            // route_name: eğer dizin sonu filename ise tekrar etmeyelim
            $lastSegment = end($segments);
            if ($lastSegment && $lastSegment === $filename) {
                // catalog.product + active
                $routeName = "{$prefix}.{$code}";
            } else {
                // catalog.product + product_image + active
                $namePart  = Str::of($filename)->replace('_', '.')->lower();
                $routeName = "{$prefix}.{$namePart}.{$code}";
            }

            $items[] =
                "        ['code' => '{$code}', 'plural' => {$plural}, 'singular' => {$singular}, 'route_name' => '{$routeName}', 'sort_order' => {$sort}],";
        }

        $itemsBlock = implode("\n", $items);

        return "#[ModuleOperation(\n    items: [\n{$itemsBlock}\n    ]\n)]";
    }
}
