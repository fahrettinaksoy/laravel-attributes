<?php

declare(strict_types=1);

namespace App\Services\Module;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use SplFileInfo;
use Throwable;

final class ModuleScannerService
{
    private const MODEL_SUFFIX = 'Model';

    private const FILE_EXTENSION = '.php';

    private const MODEL_PATTERN = '/Model\.php$/i';

    private const CACHE_KEY = 'app:modules:tree';

    private const CACHE_TTL = 3600;

    private const CACHE_TAG = 'modules';

    private const API_PREFIX = '/api/v1';

    private const BASE_NAMESPACE = 'App\\Models\\';

    private const REQUEST_NAMESPACE = 'App\\Http\\Requests\\';

    private const REST_ACTIONS = [
        'index' => ['method' => 'GET', 'has_param' => false],
        'store' => ['method' => 'POST', 'has_param' => false],
        'show' => ['method' => 'GET', 'has_param' => true],
        'update' => ['method' => 'PUT', 'has_param' => true],
        'destroy' => ['method' => 'DELETE', 'has_param' => true],
    ];

    private const REQUEST_SUFFIXES = [
        'filter' => 'FilterRequest',
        'show' => 'ShowRequest',
        'store' => 'StoreRequest',
        'update' => 'UpdateRequest',
        'destroy' => 'DestroyRequest',
    ];

    private const EXCLUDED_FILES = ['BaseModel.php', 'AbstractModel.php'];

    private string $modelsPath;

    public function __construct()
    {
        $this->modelsPath = app_path('Models');
        $this->validateModelsPath();
    }

    public function getModules(bool $useCache = false, ?int $cacheTtl = null): array
    {
        if (! $useCache) {
            return $this->scanDirectory($this->modelsPath, '');
        }

        return Cache::tags([self::CACHE_TAG])->remember(
            $this->getCacheKey(),
            $cacheTtl ?? self::CACHE_TTL,
            fn () => $this->scanDirectory($this->modelsPath, '')
        );
    }

    private function validateModelsPath(): void
    {
        if (! File::isDirectory($this->modelsPath)) {
            throw new InvalidArgumentException("Models directory not found: {$this->modelsPath}");
        }
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY.':'.md5($this->modelsPath);
    }

    private function scanDirectory(string $path, string $parentKey): array
    {
        if (! File::isDirectory($path)) {
            return [];
        }

        $modules = [];

        foreach ($this->getFiles($path) as $file) {
            if (! $this->isValidModelFile($file->getFilename())) {
                continue;
            }

            try {
                $code = $this->extractCode($file->getFilename());
                $modules[$code] = $this->buildModuleDefinition($file, $path, $parentKey);
            } catch (Throwable $e) {
                Log::warning("Failed to process root model: {$file->getFilename()}", [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        foreach ($this->getSubdirectories($path) as $subdirectory) {
            $name = basename($subdirectory);
            $code = Str::snake($name);
            $fullKey = $this->buildKey($parentKey, $code);

            try {
                $children = $this->processDirectory($subdirectory, $fullKey);

                if (! empty($children)) {
                    $modules[$code] = [
                        'code' => $fullKey,
                        'type' => 'directory',
                        'children' => $children,
                    ];
                }
            } catch (Throwable $e) {
                Log::warning("Failed to process directory: {$subdirectory}", [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $modules;
    }

    private function processDirectory(string $path, string $parentKey): array
    {
        $children = $this->processModelFiles($path, $parentKey);

        return $this->processSubdirectories($path, $parentKey, $children);
    }

    private function processModelFiles(string $path, string $parentKey): array
    {
        $modules = [];

        foreach ($this->getFiles($path) as $file) {
            if (! $this->isValidModelFile($file->getFilename())) {
                continue;
            }

            try {
                $code = $this->extractCode($file->getFilename());
                $modules[$code] = $this->buildModuleDefinition($file, $path, $parentKey);
            } catch (Throwable $e) {
                Log::warning("Failed to process model: {$file->getFilename()}", [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $modules;
    }

    private function processSubdirectories(string $path, string $parentKey, array $existing): array
    {
        foreach ($this->getSubdirectories($path) as $subdirectory) {
            $name = basename($subdirectory);
            $code = Str::snake($name);
            $newKey = $this->buildKey($parentKey, $code);

            try {
                $modelFiles = $this->collectValidModelFiles($subdirectory);

                if ($this->isSingleModuleDirectory($modelFiles, $name)) {
                    $file = reset($modelFiles);
                    $moduleCode = $this->extractCode($file->getFilename());
                    $existing[$moduleCode] = $this->buildModuleDefinition($file, $subdirectory, $parentKey);

                    continue;
                }

                $children = $this->processDirectory($subdirectory, $newKey);

                if (! empty($children)) {
                    $existing[$code] = [
                        'code' => $newKey,
                        'type' => 'directory',
                        'children' => $children,
                    ];
                }
            } catch (Throwable $e) {
                Log::warning("Failed to process subdirectory: {$subdirectory}", [
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        return $existing;
    }

    private function buildModuleDefinition(SplFileInfo $file, string $path, string $parentKey): array
    {
        $className = $this->buildClassName($path, $file->getFilename());
        $code = $this->extractCode($file->getFilename());
        $fullKey = $this->buildKey($parentKey, $code);

        return [
            'code' => $fullKey,
            'type' => 'model',
            'main' => [
                'route' => str_replace('_', '/', $fullKey),
                'actions' => $this->extractActions($className),
                'fields' => $this->extractFields($className),
            ],
            'relations' => $this->extractRelations($className),
        ];
    }

    private function extractFields(string $className): array
    {
        if (! $this->isValidModel($className)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $fields = [];

            foreach ($reflection->getProperties() as $property) {
                $field = $this->buildFieldDefinition($property);

                if ($field !== null) {
                    $fields[$property->getName()] = $field;
                }
            }

            return $fields;
        } catch (ReflectionException $e) {
            Log::error("Failed to extract fields: {$className}", ['exception' => $e->getMessage()]);

            return [];
        }
    }

    private function buildFieldDefinition(ReflectionProperty $property): ?array
    {
        $field = [
            'type' => '',
            'default' => null,
            'value' => null,
            'relationship' => '',
            'options' => [],
            'sort_order' => 0,
            'actions' => [],
            'tables' => [],
            'validation' => [],
        ];

        $hasAttributes = false;
        $hasAttributes |= $this->applyFormFieldAttribute($property, $field);
        $hasAttributes |= $this->applyTableColumnAttribute($property, $field);
        $hasAttributes |= $this->applyActionTypeAttribute($property, $field);

        return $hasAttributes ? $field : null;
    }

    private function applyFormFieldAttribute(ReflectionProperty $property, array &$field): bool
    {
        $attributes = $property->getAttributes(FormField::class);

        if (empty($attributes)) {
            return false;
        }

        try {
            $instance = $attributes[0]->newInstance();
            $field['type'] = $instance->type ?? '';
            $field['default'] = $instance->default;
            $field['value'] = $instance->value;
            $field['relationship'] = $instance->relationship ?? '';
            $field['options'] = $this->normalizeOptions($instance->options ?? []);
            $field['sort_order'] = $instance->sort_order ?? 0;

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to apply FormField attribute', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    private function applyTableColumnAttribute(ReflectionProperty $property, array &$field): bool
    {
        $attributes = $property->getAttributes(TableColumn::class);

        if (empty($attributes)) {
            return false;
        }

        try {
            $instance = $attributes[0]->newInstance();
            $field['tables'] = $instance->actions ?? [];

            if (! empty($instance->sorting)) {
                $field['sorting'] = $instance->sorting;
            }

            if (! empty($instance->primaryKey)) {
                $field['meta']['primary_key'] = $instance->primaryKey;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to apply TableColumn attribute', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    private function applyActionTypeAttribute(ReflectionProperty $property, array &$field): bool
    {
        $attributes = $property->getAttributes(ActionType::class);

        if (empty($attributes)) {
            return false;
        }

        try {
            $instance = $attributes[0]->newInstance();
            $field['actions'] = $instance->actions ?? [];

            return true;
        } catch (Throwable $e) {
            Log::warning('Failed to apply ActionType attribute', ['exception' => $e->getMessage()]);

            return false;
        }
    }

    private function normalizeOptions(array $options): array
    {
        return collect($options)->map(fn ($label, $key) => [
            'value' => (string) $key,
            'label' => (string) $label,
        ])->values()->toArray();
    }

    private function extractActions(string $className): array
    {
        if (! $this->isValidModel($className)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $actionNames = $this->collectActionNames($reflection);
            $routeBase = $this->buildRouteBase($className);
            $actions = $this->buildActions($actionNames, $routeBase);

            return $this->attachValidation($className, $actions);
        } catch (Throwable $e) {
            Log::error("Failed to extract actions: {$className}", ['exception' => $e->getMessage()]);

            return [];
        }
    }

    private function collectActionNames(ReflectionClass $reflection): array
    {
        $actions = [];

        foreach ($reflection->getProperties() as $property) {
            foreach ($property->getAttributes(ActionType::class) as $attribute) {
                try {
                    $instance = $attribute->newInstance();
                    $actionList = $instance->actions ?? [];

                    if (is_array($actionList)) {
                        $normalized = array_map(
                            fn ($action) => $action === 'filter' ? 'index' : $action,
                            $actionList
                        );
                        $actions = array_merge($actions, $normalized);
                    }
                } catch (Throwable $e) {
                    Log::warning('Failed to collect action names', ['exception' => $e->getMessage()]);
                }
            }
        }

        return array_values(array_unique(array_filter($actions)));
    }

    private function buildActions(array $actionNames, string $routeBase): array
    {
        $actions = [];

        foreach ($actionNames as $name) {
            if (empty($name) || ! is_string($name)) {
                continue;
            }

            $config = self::REST_ACTIONS[$name] ?? ['method' => 'POST', 'has_param' => true];
            $route = $this->buildRoute($routeBase, $name, $config['has_param']);

            $actions[$name] = [
                'name' => $name,
                'method' => $config['method'],
                'route' => $this->buildFullRoute($route),
            ];
        }

        return $actions;
    }

    private function buildRoute(string $base, string $action, bool $hasParam): string
    {
        $route = '/'.$base;

        if ($hasParam) {
            $modelName = basename($base);
            $route .= '/{'.$modelName.'_id}';
        }

        if (! array_key_exists($action, self::REST_ACTIONS)) {
            $route .= '/'.$action;
        }

        return $route;
    }

    private function attachValidation(string $className, array $actions): array
    {
        $modelName = $this->getModelName($className);
        $namespace = $this->getRequestNamespace($className);

        foreach ($actions as $action => $data) {
            $requestClass = $this->buildRequestClass($namespace, $modelName, $action);
            $actions[$action]['validation'] = $this->extractValidationRules($requestClass);
        }

        return $actions;
    }

    private function buildRequestClass(string $namespace, string $model, string $action, string $suffix = ''): string
    {
        $requestSuffix = self::REQUEST_SUFFIXES[$action] ?? Str::ucfirst($action).'Request';

        return self::REQUEST_NAMESPACE.$namespace.'\\'.$model.$suffix.$requestSuffix;
    }

    private function extractValidationRules(string $className): ?array
    {
        if (! $this->isValidRequest($className)) {
            return null;
        }

        try {
            $instance = new $className;

            return [
                'rules' => method_exists($instance, 'rules') ? $instance->rules() : [],
            ];
        } catch (Throwable $e) {
            Log::debug("Failed to extract validation: {$className}", ['exception' => $e->getMessage()]);

            return null;
        }
    }

    private function isValidRequest(string $className): bool
    {
        return class_exists($className) && File::exists($this->getRequestPath($className));
    }

    private function getRequestPath(string $className): string
    {
        $relative = str_replace(['App\\', '\\'], ['', '/'], $className);

        return app_path($relative.self::FILE_EXTENSION);
    }

    private function extractRelations(string $className): array
    {
        if (! $this->isValidModel($className)) {
            return [];
        }

        try {
            $reflection = new ReflectionClass($className);
            $instance = $reflection->newInstanceWithoutConstructor();
            $relations = [];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $this->isValidRelationMethod($method, $className, $instance)) {
                    continue;
                }

                try {
                    $relation = $instance->{$method->getName()}();
                    $relatedClass = get_class($relation->getRelated());
                    $key = $this->buildRelationKey($relatedClass);

                    $relations[$key] = $this->buildRelationDefinition($className, $method->getName(), $relatedClass);
                } catch (Throwable $e) {
                    Log::debug("Failed to process relation: {$method->getName()}", [
                        'model' => $className,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }

            ksort($relations);

            return $relations;
        } catch (Throwable $e) {
            Log::error("Failed to extract relations: {$className}", ['exception' => $e->getMessage()]);

            return [];
        }
    }

    private function isValidRelationMethod(ReflectionMethod $method, string $className, $instance): bool
    {
        if ($method->getDeclaringClass()->getName() !== $className) {
            return false;
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        $returnType = $method->getReturnType();
        if ($returnType instanceof ReflectionNamedType && is_a($returnType->getName(), HasMany::class, true)) {
            return true;
        }

        try {
            return $instance->{$method->getName()}() instanceof HasMany;
        } catch (Throwable) {
            return false;
        }
    }

    private function buildRelationDefinition(string $parentClass, string $method, string $relatedClass): array
    {
        return [
            'code' => Str::snake($method),
            'route' => $this->buildRelationRoute($parentClass, $method),
            'main' => [
                'actions' => $this->buildRelationActions($parentClass, $method, $relatedClass),
                'fields' => $this->extractFields($relatedClass),
            ],
            'relations' => $this->extractRelations($relatedClass),
        ];
    }

    private function buildRelationRoute(string $parentClass, string $method): string
    {
        $base = $this->buildRouteBase($parentClass);
        $relation = Str::snake($method);
        $parentName = $this->getModelName($parentClass);
        $parentId = Str::snake($parentName).'_id';

        return $base.'/{'.$parentId.'}/'.$relation;
    }

    private function buildRelationActions(string $parentClass, string $method, string $relatedClass): array
    {
        try {
            $relatedReflection = new ReflectionClass($relatedClass);
            $actionNames = $this->collectActionNames($relatedReflection);

            if (empty($actionNames)) {
                $actionNames = array_keys(self::REST_ACTIONS);
            }

            $base = $this->buildRouteBase($parentClass);
            $relation = Str::snake($method);
            $parentName = $this->getModelName($parentClass);
            $parentId = Str::snake($parentName).'_id';
            $relationId = $relation.'_id';
            $routeBase = $base.'/{'.$parentId.'}/'.$relation;

            $actions = [];

            foreach ($actionNames as $actionName) {
                $config = self::REST_ACTIONS[$actionName] ?? ['method' => 'POST', 'has_param' => true];

                $route = '/'.$routeBase;

                if ($config['has_param']) {
                    $route .= '/{'.$relationId.'}';
                }

                if (! array_key_exists($actionName, self::REST_ACTIONS)) {
                    $route .= '/'.$actionName;
                }

                $actions[$actionName] = [
                    'name' => $actionName,
                    'method' => $config['method'],
                    'route' => $this->buildFullRoute($route),
                ];
            }

            return $this->attachRelationValidation($parentClass, $method, $actions);

        } catch (Throwable $e) {
            Log::debug("Failed to build relation actions: {$method}", [
                'parent' => $parentClass,
                'exception' => $e->getMessage(),
            ]);

            return [];
        }
    }

    private function attachRelationValidation(string $className, string $method, array $actions): array
    {
        $modelName = $this->getModelName($className);
        $namespace = $this->getRequestNamespace($className);
        $relation = Str::studly($method);

        foreach ($actions as $name => $data) {
            $requestClass = $this->buildRequestClass($namespace, $modelName, $name, $relation);
            $actions[$name]['validation'] = $this->extractValidationRules($requestClass);
        }

        return $actions;
    }

    private function buildRelationKey(string $className): string
    {
        $base = class_basename($className);
        $clean = str_ireplace(self::MODEL_SUFFIX, '', $base);

        return Str::snake($clean);
    }

    private function getModelName(string $className): string
    {
        return str_ireplace(self::MODEL_SUFFIX, '', class_basename($className));
    }

    private function getRequestNamespace(string $className): string
    {
        $relative = Str::after($className, self::BASE_NAMESPACE);
        $parts = explode('\\', $relative);
        array_pop($parts);

        return implode('\\', $parts);
    }

    private function buildRouteBase(string $className): string
    {
        $relative = Str::after($className, self::BASE_NAMESPACE);
        $parts = explode('\\', $relative);
        $last = end($parts);

        if ($last && Str::endsWith($last, self::MODEL_SUFFIX)) {
            array_pop($parts);
        }

        return collect($parts)->map(fn ($part) => Str::snake($part))->implode('/');
    }

    private function buildFullRoute(string $pattern): string
    {
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

        return $baseUrl.self::API_PREFIX.$pattern;
    }

    private function isValidModel(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        return $reflection->isSubclassOf(Model::class) && ! $reflection->isAbstract() && ! $reflection->isInterface();
    }

    private function collectValidModelFiles(string $path): array
    {
        return array_filter(
            $this->getFiles($path),
            fn (SplFileInfo $file) => $this->isValidModelFile($file->getFilename())
        );
    }

    private function isValidModelFile(string $filename): bool
    {
        return preg_match(self::MODEL_PATTERN, $filename) && ! in_array($filename, self::EXCLUDED_FILES, true);
    }

    private function extractCode(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $clean = str_replace(self::MODEL_SUFFIX, '', $base);

        return Str::snake($clean);
    }

    private function buildClassName(string $path, string $filename): string
    {
        $relative = str_replace(app_path('Models'), '', $path);
        $namespace = trim(str_replace(['/', '\\'], '\\', $relative), '\\');
        $class = pathinfo($filename, PATHINFO_FILENAME);

        return self::BASE_NAMESPACE.($namespace ? $namespace.'\\' : '').$class;
    }

    private function buildKey(string $parent, string $current): string
    {
        return $parent ? "{$parent}_{$current}" : $current;
    }

    private function isSingleModuleDirectory(array $modelFiles, string $directoryName): bool
    {
        if (count($modelFiles) !== 1) {
            return false;
        }

        $file = reset($modelFiles);

        if (! $file instanceof SplFileInfo) {
            return false;
        }

        $base = str_replace(self::MODEL_SUFFIX.self::FILE_EXTENSION, '', $file->getFilename());

        return strcasecmp($base, $directoryName) === 0;
    }

    private function getSubdirectories(string $path): array
    {
        try {
            return File::directories($path);
        } catch (Throwable $e) {
            Log::error("Failed to read subdirectories: {$path}", ['exception' => $e->getMessage()]);

            return [];
        }
    }

    private function getFiles(string $path): array
    {
        try {
            return File::files($path);
        } catch (Throwable $e) {
            Log::error("Failed to read files: {$path}", ['exception' => $e->getMessage()]);

            return [];
        }
    }
}
