<?php

declare(strict_types=1);

namespace App\Traits;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
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

trait ScansModulesTrait
{
    private const MODEL_SUFFIX = 'Model';
    private const MODEL_FILE_EXTENSION = '.php';
    private const MODEL_FILE_PATTERN = '/Model\.php$/i';
    private const CACHE_KEY_MODULE_TREE = 'app:modules:tree';
    private const CACHE_TTL_MODULES = 3600; // 1 hour
    private const API_VERSION_PREFIX = '/api/v1';
    private const BASE_NAMESPACE = 'App\\Models\\';
    private const REQUEST_NAMESPACE = 'App\\Http\\Requests\\';

    private const STANDARD_REST_ACTIONS = [
        'index' => ['method' => 'GET', 'has_param' => false],
        'store' => ['method' => 'POST', 'has_param' => false],
        'show' => ['method' => 'GET', 'has_param' => true],
        'update' => ['method' => 'PUT', 'has_param' => true],
        'destroy' => ['method' => 'DELETE', 'has_param' => true],
    ];

    private const ACTION_REQUEST_SUFFIXES = [
        'filter' => 'FilterRequest',
        'show' => 'ShowRequest',
        'store' => 'StoreRequest',
        'update' => 'UpdateRequest',
        'destroy' => 'DestroyRequest',
    ];

    protected array $excludedModelFiles = [
        'BaseModel.php',
        'AbstractModel.php',
    ];

    protected string $modelsBasePath = '';

    protected string $cacheTag = 'modules';

    public function getModules(bool $shouldUseCache = false, ?int $cacheTtl = null): array
    {
        $this->ensureModelsPathInitialized();

        if (! $shouldUseCache) {
            return $this->scanModulesRecursively($this->modelsBasePath, '');
        }

        $cacheKey = $this->buildModuleCacheKey();
        $ttl = $cacheTtl ?? self::CACHE_TTL_MODULES;

        return Cache::tags([$this->cacheTag])
            ->remember($cacheKey, $ttl, function (): array {
                return $this->scanModulesRecursively($this->modelsBasePath, '');
            });
    }

    public function clearModuleCache(): bool
    {
        return Cache::tags([$this->cacheTag])->flush();
    }

    private function ensureModelsPathInitialized(): void
    {
        if (empty($this->modelsBasePath)) {
            $this->modelsBasePath = app_path('Models');
        }

        if (! File::isDirectory($this->modelsBasePath)) {
            throw new InvalidArgumentException(
                "Models directory does not exist: {$this->modelsBasePath}",
            );
        }
    }

    private function buildModuleCacheKey(): string
    {
        $pathHash = md5($this->modelsBasePath);

        return self::CACHE_KEY_MODULE_TREE . ':' . $pathHash;
    }

    protected function scanModulesRecursively(string $directoryPath, string $parentNamespaceKey): array
    {
        if (! File::isDirectory($directoryPath)) {
            return [];
        }

        $discoveredModules = [];
        $subDirectories = $this->getSubDirectories($directoryPath);

        foreach ($subDirectories as $subDirectoryPath) {
            $directoryName = basename($subDirectoryPath);
            $directoryCode = $this->convertToSnakeCase($directoryName);
            $fullNamespaceKey = $this->buildNamespaceKey($parentNamespaceKey, $directoryCode);

            try {
                $childModules = $this->processDirectoryContents($subDirectoryPath, $fullNamespaceKey);

                if (! empty($childModules)) {
                    $discoveredModules[$directoryCode] = [
                        'code' => $fullNamespaceKey,
                        'type' => 'directory',
                        'children' => $childModules,
                    ];
                }
            } catch (Throwable $exception) {
                Log::warning("Failed to process directory: {$subDirectoryPath}", [
                    'exception' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                ]);

                continue;
            }
        }

        return $discoveredModules;
    }

    private function getSubDirectories(string $directoryPath): array
    {
        try {
            return File::directories($directoryPath);
        } catch (Throwable $exception) {
            Log::error("Failed to read directories from: {$directoryPath}", [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    protected function processDirectoryContents(string $directoryPath, string $parentNamespaceKey): array
    {
        $processedChildren = [];
        $processedChildren = $this->processModelFilesInDirectory($directoryPath, $parentNamespaceKey, $processedChildren);

        return $this->processSubDirectories($directoryPath, $parentNamespaceKey, $processedChildren);
    }

    private function processModelFilesInDirectory(string $directoryPath, string $parentNamespaceKey, array $existingChildren): array
    {
        $modelFiles = $this->getFilesFromDirectory($directoryPath);

        foreach ($modelFiles as $modelFile) {
            $fileName = $modelFile->getFilename();

            if (! $this->isValidModelFile($fileName)) {
                continue;
            }

            try {
                $moduleCode = $this->extractCodeFromFileName($fileName);
                $moduleDefinition = $this->createModuleDefinition(
                    $modelFile,
                    $directoryPath,
                    $parentNamespaceKey,
                );

                $existingChildren[$moduleCode] = $moduleDefinition;
            } catch (Throwable $exception) {
                Log::warning("Failed to process model file: {$fileName}", [
                    'directory' => $directoryPath,
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }
        }

        return $existingChildren;
    }

    private function getFilesFromDirectory(string $directoryPath): array
    {
        try {
            return File::files($directoryPath);
        } catch (Throwable $exception) {
            Log::error("Failed to read files from directory: {$directoryPath}", [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function processSubDirectories(string $directoryPath, string $parentNamespaceKey, array $existingChildren): array
    {
        $subDirectories = $this->getSubDirectories($directoryPath);

        foreach ($subDirectories as $subDirectoryPath) {
            $directoryName = basename($subDirectoryPath);
            $directoryCode = $this->convertToSnakeCase($directoryName);
            $newNamespaceKey = $this->buildNamespaceKey($parentNamespaceKey, $directoryCode);

            try {
                $modelFilesInSubDir = $this->collectValidModelFiles($subDirectoryPath);

                if ($this->shouldTreatAsSingleModule($modelFilesInSubDir, $directoryName)) {
                    $modelFile = $modelFilesInSubDir[0];
                    $moduleCode = $this->extractCodeFromFileName($modelFile->getFilename());
                    $moduleDefinition = $this->createModuleDefinition($modelFile, $subDirectoryPath, $parentNamespaceKey);
                    $existingChildren[$moduleCode] = $moduleDefinition;

                    continue;
                }

                $nestedModules = $this->processDirectoryContents($subDirectoryPath, $newNamespaceKey);

                if (! empty($nestedModules)) {
                    $existingChildren[$directoryCode] = [
                        'code' => $newNamespaceKey,
                        'type' => 'directory',
                        'children' => $nestedModules,
                    ];
                }
            } catch (Throwable $exception) {
                Log::warning("Failed to process subdirectory: {$subDirectoryPath}", [
                    'exception' => $exception->getMessage(),
                ]);

                continue;
            }
        }

        return $existingChildren;
    }

    protected function createModuleDefinition(SplFileInfo $modelFile, string $directoryPath, string $parentNamespaceKey): array
    {
        $fileName = $modelFile->getFilename();
        $fullyQualifiedClassName = $this->buildFullyQualifiedClassName($directoryPath, $fileName);
        $moduleCode = $this->extractCodeFromFileName($fileName);
        $fullNamespaceKey = $this->buildNamespaceKey($parentNamespaceKey, $moduleCode);

        return [
            'code' => $fullNamespaceKey,
            'type' => 'model',
            'main' => [
                'route' => str_replace('_', '/', $fullNamespaceKey),
                'actions' => $this->extractActionsFromModel($fullyQualifiedClassName),
                'fields' => $this->extractFieldsFromModel($fullyQualifiedClassName),
            ],
            'pivots' => $this->extractPivotRelations($fullyQualifiedClassName),
        ];
    }

    protected function extractFieldsFromModel(string $fullyQualifiedClassName): array
    {
        if (! $this->isValidModelClass($fullyQualifiedClassName)) {
            return [];
        }

        try {
            $classReflection = new ReflectionClass($fullyQualifiedClassName);
            $extractedFields = [];

            foreach ($classReflection->getProperties() as $property) {
                $fieldDefinition = $this->processPropertyAttributes($property);

                if ($fieldDefinition !== null) {
                    $extractedFields[$property->getName()] = $fieldDefinition;
                }
            }

            return $extractedFields;
        } catch (ReflectionException $exception) {
            Log::error("Failed to extract fields from model: {$fullyQualifiedClassName}", [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function isValidModelClass(string $className): bool
    {
        if (! class_exists($className)) {
            return false;
        }

        $reflection = new ReflectionClass($className);

        return $reflection->isSubclassOf(Model::class) &&
            ! $reflection->isAbstract() &&
            ! $reflection->isInterface();
    }

    private function processPropertyAttributes(ReflectionProperty $property): ?array
    {
        $fieldDefinition = $this->createDefaultFieldDefinition();
        $hasRelevantAttributes = false;

        if ($this->applyFormFieldAttributes($property, $fieldDefinition)) {
            $hasRelevantAttributes = true;
        }

        if ($this->applyTableColumnAttributes($property, $fieldDefinition)) {
            $hasRelevantAttributes = true;
        }

        if ($this->applyActionTypeAttributes($property, $fieldDefinition)) {
            $hasRelevantAttributes = true;
        }

        return $hasRelevantAttributes ? $fieldDefinition : null;
    }

    private function createDefaultFieldDefinition(): array
    {
        return [
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
    }

    private function applyFormFieldAttributes(ReflectionProperty $property, array &$fieldDefinition): bool
    {
        $formFieldAttributes = $property->getAttributes(FormField::class);

        if (empty($formFieldAttributes)) {
            return false;
        }

        try {
            $formFieldInstance = $formFieldAttributes[0]->newInstance();

            $fieldDefinition['type'] = $formFieldInstance->type ?? '';
            $fieldDefinition['default'] = $formFieldInstance->default;
            $fieldDefinition['value'] = $formFieldInstance->value;
            $fieldDefinition['relationship'] = $formFieldInstance->relationship ?? '';
            $fieldDefinition['options'] = $this->normalizeFieldOptions($formFieldInstance->options ?? []);
            $fieldDefinition['sort_order'] = $formFieldInstance->sort_order ?? 0;

            return true;
        } catch (Throwable $exception) {
            Log::warning("Failed to process FormField attribute for property: {$property->getName()}", [
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function applyTableColumnAttributes(ReflectionProperty $property, array &$fieldDefinition): bool
    {
        $tableColumnAttributes = $property->getAttributes(TableColumn::class);

        if (empty($tableColumnAttributes)) {
            return false;
        }

        try {
            $tableColumnInstance = $tableColumnAttributes[0]->newInstance();
            $fieldDefinition['tables'] = $tableColumnInstance->actions ?? [];

            if (! empty($tableColumnInstance->sorting)) {
                $fieldDefinition['sorting'] = $tableColumnInstance->sorting;
            }

            if (! empty($tableColumnInstance->primaryKey)) {
                $fieldDefinition['meta']['primary_key'] = $tableColumnInstance->primaryKey;
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning("Failed to process TableColumn attribute for property: {$property->getName()}", [
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function applyActionTypeAttributes(ReflectionProperty $property, array &$fieldDefinition): bool
    {
        $actionTypeAttributes = $property->getAttributes(ActionType::class);

        if (empty($actionTypeAttributes)) {
            return false;
        }

        try {
            $actionTypeInstance = $actionTypeAttributes[0]->newInstance();
            $fieldDefinition['actions'] = $actionTypeInstance->actions ?? [];

            return true;
        } catch (Throwable $exception) {
            Log::warning("Failed to process ActionType attribute for property: {$property->getName()}", [
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    private function normalizeFieldOptions($options): array
    {
        if (! is_array($options)) {
            return [];
        }

        $normalizedOptions = [];

        foreach ($options as $key => $value) {
            $normalizedOptions[] = ['value' => (string) $key, 'label' => (string) $value];
        }

        return $normalizedOptions;
    }

    protected function extractActionsFromModel(string $fullyQualifiedClassName): array
    {
        if (! $this->isValidModelClass($fullyQualifiedClassName)) {
            return [];
        }

        try {
            $routeBase = $this->generateRouteBase($fullyQualifiedClassName);
            $classReflection = new ReflectionClass($fullyQualifiedClassName);
            $rawActions = $this->collectActionsFromProperties($classReflection);
            $processedActions = $this->buildActionsArray($rawActions, $routeBase);

            return $this->attachValidationDataToActions($fullyQualifiedClassName, $processedActions);
        } catch (ReflectionException $exception) {
            Log::error("Failed to extract actions from model: {$fullyQualifiedClassName}", [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function collectActionsFromProperties(ReflectionClass $classReflection): array
    {
        $collectedActions = [];

        foreach ($classReflection->getProperties() as $property) {
            foreach ($property->getAttributes(ActionType::class) as $attribute) {
                try {
                    $actionTypeInstance = $attribute->newInstance();
                    $actions = $actionTypeInstance->actions ?? [];

                    if (is_array($actions)) {
                        $actions = array_map(function ($action) {
                            return $action === 'filter' ? 'index' : $action;
                        }, $actions);

                        $collectedActions = array_merge($collectedActions, $actions);
                    }
                } catch (Throwable $exception) {
                    Log::warning('Failed to process ActionType attribute', [
                        'property' => $property->getName(),
                        'exception' => $exception->getMessage(),
                    ]);

                    continue;
                }
            }
        }

        return array_values(array_unique(array_filter($collectedActions)));
    }

    private function buildActionsArray(array $rawActions, string $routeBase): array
    {
        $builtActions = [];
        $standardActionsWithParams = [
            'index' => ['method' => 'GET', 'has_param' => false],
            'store' => ['method' => 'POST', 'has_param' => false],
            'show' => ['method' => 'GET', 'has_param' => true],
            'update' => ['method' => 'PUT', 'has_param' => true],
            'destroy' => ['method' => 'DELETE', 'has_param' => true],
        ];

        foreach ($rawActions as $actionName) {
            if (empty($actionName) || ! is_string($actionName)) {
                continue;
            }

            $actionConfig = $standardActionsWithParams[$actionName] ?? ['method' => 'GET', 'has_param' => false];
            $httpMethod = $actionConfig['method'];
            $hasParam = $actionConfig['has_param'];

            $routePattern = $this->buildRoutePattern($routeBase, $actionName, $hasParam);
            $fullRoute = $this->buildFullApiRoute($routePattern);
            $builtActions[$actionName] = [
                'name' => $actionName,
                'method' => $httpMethod,
                'route' => $fullRoute,
            ];
        }

        return $builtActions;
    }

    private function buildRoutePattern(string $routeBase, string $actionName, bool $hasParam): string
    {
        $pattern = '/' . $routeBase;

        if ($hasParam) {
            $modelName = basename($routeBase);
            $idParam = $modelName . '_id';
            $pattern .= '/{' . $idParam . '}';
        }

        if (! in_array($actionName, ['index', 'store', 'show', 'update', 'destroy'])) {
            $pattern .= '/' . $actionName;
        }

        return $pattern;
    }

    protected function attachValidationDataToActions(string $modelClassName, array $actions): array
    {
        $modelShortName = $this->extractModelShortName($modelClassName);
        $httpRequestNamespace = $this->extractHttpRequestNamespace($modelClassName);

        foreach ($actions as $actionName => $actionData) {
            $requestClassName = $this->buildRequestClassName($httpRequestNamespace, $modelShortName, $actionName);
            $actions[$actionName]['validation'] = $this->extractValidationRules($requestClassName);
        }

        return $actions;
    }

    private function buildRequestClassName(string $namespace, string $modelName, string $actionName, string $relationName = ''): string
    {
        $suffix = self::ACTION_REQUEST_SUFFIXES[$actionName] ?? Str::ucfirst($actionName) . 'Request';

        return sprintf('%s%s\\%s%s%s', self::REQUEST_NAMESPACE, $namespace, $modelName, $relationName, $suffix);
    }

    private function extractValidationRules(string $requestClassName): ?array
    {
        if (! $this->isValidRequestClass($requestClassName)) {
            return null;
        }

        try {
            $requestInstance = new $requestClassName;

            return [
                'rules' => method_exists($requestInstance, 'rules') ? $requestInstance->rules() : [],
                'messages' => method_exists($requestInstance, 'messages') ? $requestInstance->messages() : [],
                'attributes' => method_exists($requestInstance, 'attributes') ? $requestInstance->attributes() : [],
                'class' => $requestClassName,
            ];
        } catch (Throwable $exception) {
            Log::debug("Failed to instantiate request class: {$requestClassName}", [
                'exception' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function isValidRequestClass(string $requestClassName): bool
    {
        if (! class_exists($requestClassName)) {
            return false;
        }

        $requestFilePath = $this->buildRequestFilePath($requestClassName);

        return File::exists($requestFilePath);
    }

    protected function buildRequestFilePath(string $requestClassName): string
    {
        $relativePath = str_replace(['App\\', '\\'], ['', '/'], $requestClassName);

        return app_path($relativePath . self::MODEL_FILE_EXTENSION);
    }

    protected function extractModelShortName(string $fullyQualifiedClassName): string
    {
        $classBaseName = class_basename($fullyQualifiedClassName);

        return str_ireplace(self::MODEL_SUFFIX, '', $classBaseName);
    }

    protected function extractHttpRequestNamespace(string $fullyQualifiedClassName): string
    {
        $trimmedClassName = Str::after($fullyQualifiedClassName, self::BASE_NAMESPACE);
        $namespaceParts = explode('\\', $trimmedClassName);
        array_pop($namespaceParts); // Remove class name

        return implode('\\', $namespaceParts);
    }

    protected function generateRouteBase(string $fullyQualifiedClassName): string
    {
        $trimmedClassName = Str::after($fullyQualifiedClassName, self::BASE_NAMESPACE);
        $classNameParts = explode('\\', $trimmedClassName);
        $lastPart = end($classNameParts);

        if ($lastPart && Str::endsWith($lastPart, self::MODEL_SUFFIX)) {
            array_pop($classNameParts);
        }

        return Collection::make($classNameParts)
            ->map(fn (string $segment): string => $this->convertToSnakeCase($segment))
            ->filter()
            ->implode('/');
    }

    private function convertToSnakeCase(string $value): string
    {
        return Str::snake($value);
    }

    protected function collectValidModelFiles(string $directoryPath): array
    {
        $validModelFiles = [];
        $filesInDirectory = $this->getFilesFromDirectory($directoryPath);

        foreach ($filesInDirectory as $file) {
            if ($this->isValidModelFile($file->getFilename())) {
                $validModelFiles[] = $file;
            }
        }

        return $validModelFiles;
    }

    protected function isValidModelFile(string $fileName): bool
    {
        return $this->isModelFile($fileName) && ! $this->isExcludedFile($fileName);
    }

    protected function isModelFile(string $fileName): bool
    {
        return (bool) preg_match(self::MODEL_FILE_PATTERN, $fileName);
    }

    protected function isExcludedFile(string $fileName): bool
    {
        return in_array($fileName, $this->excludedModelFiles, true);
    }

    protected function extractCodeFromFileName(string $fileName): string
    {
        $baseFileName = pathinfo($fileName, PATHINFO_FILENAME);
        $cleanName = str_replace(self::MODEL_SUFFIX, '', $baseFileName);

        return $this->convertToSnakeCase($cleanName);
    }

    protected function buildFullyQualifiedClassName(string $directoryPath, string $fileName): string
    {
        $relativePath = str_replace(app_path('Models'), '', $directoryPath);
        $namespacePart = str_replace(['/', '\\'], '\\', trim($relativePath, '/\\'));
        $className = pathinfo($fileName, PATHINFO_FILENAME);
        $namespace = self::BASE_NAMESPACE;
        if (! empty($namespacePart)) {
            $namespace .= trim($namespacePart, '\\') . '\\';
        }

        return $namespace . $className;
    }

    private function buildNamespaceKey(string $parentKey, string $currentKey): string
    {
        if (empty($parentKey)) {
            return $currentKey;
        }

        return "{$parentKey}_{$currentKey}";
    }

    private function shouldTreatAsSingleModule(array $modelFiles, string $directoryName): bool
    {
        if (count($modelFiles) !== 1) {
            return false;
        }

        $modelFile = $modelFiles[0];
        $baseFileName = str_replace(self::MODEL_SUFFIX . self::MODEL_FILE_EXTENSION, '', $modelFile->getFilename());

        return strcasecmp($baseFileName, $directoryName) === 0;
    }

    private function buildFullApiRoute(string $routePattern): string
    {
        $baseUrl = rtrim(config('app.url', 'http://localhost'), '/');

        return $baseUrl . self::API_VERSION_PREFIX . $routePattern;
    }

    protected function extractPivotRelations(string $fullyQualifiedClassName): array
    {
        if (! $this->isValidModelClass($fullyQualifiedClassName)) {
            return [];
        }

        try {
            $classReflection = new ReflectionClass($fullyQualifiedClassName);
            $modelInstance = $classReflection->newInstanceWithoutConstructor();
            $discoveredPivots = [];

            foreach ($classReflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (! $this->isValidRelationMethod($method, $fullyQualifiedClassName, $modelInstance)) {
                    continue;
                }

                try {
                    $relationInstance = $modelInstance->{$method->getName()}();
                    $relatedClassName = get_class($relationInstance->getRelated());
                    $pivotData = $this->buildPivotDefinition($fullyQualifiedClassName, $method->getName(), $relatedClassName);
                    $pivotKey = $this->generatePivotKey($relatedClassName);
                    $discoveredPivots[$pivotKey] = $pivotData;
                } catch (Throwable $exception) {
                    Log::debug("Failed to process pivot relation: {$method->getName()}", [
                        'model' => $fullyQualifiedClassName,
                        'exception' => $exception->getMessage(),
                    ]);

                    continue;
                }
            }

            ksort($discoveredPivots);

            return $discoveredPivots;
        } catch (ReflectionException $exception) {
            Log::error("Failed to extract pivot relations from model: {$fullyQualifiedClassName}", [
                'exception' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    private function isValidRelationMethod(ReflectionMethod $method, string $className, $modelInstance): bool
    {
        if ($method->getDeclaringClass()->getName() !== $className) {
            return false;
        }

        if ($method->getNumberOfRequiredParameters() > 0) {
            return false;
        }

        $returnType = $method->getReturnType();
        if ($returnType instanceof ReflectionNamedType) {
            $returnTypeName = $returnType->getName();
            if (is_a($returnTypeName, HasMany::class, true)) {
                return true;
            }
        }

        try {
            $relationResult = $modelInstance->{$method->getName()}();

            return $relationResult instanceof HasMany;
        } catch (Throwable) {
            return false;
        }
    }

    private function buildPivotDefinition(string $parentClassName, string $methodName, string $relatedClassName): array
    {
        return [
            'code' => $this->convertToSnakeCase($methodName),
            'route' => $this->generatePivotRoute($parentClassName, $methodName),
            'actions' => $this->generatePivotActions($parentClassName, $methodName),
            'fields' => $this->extractFieldsFromModel($relatedClassName),
        ];
    }

    protected function generatePivotRoute(string $parentClassName, string $methodName): string
    {
        $baseRoute = $this->generateRouteBase($parentClassName);
        $relationName = $this->convertToSnakeCase($methodName);
        $parentModelName = $this->extractModelShortName($parentClassName);
        $parentIdParam = $this->convertToSnakeCase($parentModelName) . '_id';

        return $baseRoute . '/{' . $parentIdParam . '}/' . $relationName;
    }

    protected function generatePivotActions(string $parentClassName, string $methodName): array
    {
        $baseRoute = $this->generateRouteBase($parentClassName);
        $relationName = $this->convertToSnakeCase($methodName);
        $parentModelName = $this->extractModelShortName($parentClassName);
        $parentIdParam = $this->convertToSnakeCase($parentModelName) . '_id';
        $relationIdParam = $relationName . '_id';

        $routeBase = $baseRoute . '/{' . $parentIdParam . '}/' . $relationName;

        $actions = [
            'filter' => [
                'name' => 'filter',
                'method' => 'GET',
                'route' => $this->buildFullApiRoute('/' . $routeBase),
            ],
            'store' => [
                'name' => 'store',
                'method' => 'POST',
                'route' => $this->buildFullApiRoute('/' . $routeBase),
            ],
            'show' => [
                'name' => 'show',
                'method' => 'GET',
                'route' => $this->buildFullApiRoute('/' . $routeBase . '/{' . $relationIdParam . '}'),
            ],
            'update' => [
                'name' => 'update',
                'method' => 'PUT',
                'route' => $this->buildFullApiRoute('/' . $routeBase . '/{' . $relationIdParam . '}'),
            ],
            'destroy' => [
                'name' => 'destroy',
                'method' => 'DELETE',
                'route' => $this->buildFullApiRoute('/' . $routeBase . '/{' . $relationIdParam . '}'),
            ],
        ];

        return $this->attachPivotValidationData($parentClassName, $methodName, $actions);
    }

    protected function attachPivotValidationData(string $modelClassName, string $methodName, array $actions): array
    {
        $modelShortName = $this->extractModelShortName($modelClassName);
        $httpRequestNamespace = $this->extractHttpRequestNamespace($modelClassName);
        $relationName = Str::studly($methodName);

        foreach ($actions as $actionName => $actionData) {
            $requestClassName = $this->buildRequestClassName($httpRequestNamespace, $modelShortName, $actionName, $relationName);
            $actions[$actionName]['validation'] = $this->extractValidationRules($requestClassName);
        }

        return $actions;
    }

    private function generatePivotKey(string $relatedClassName): string
    {
        $relatedBaseName = class_basename($relatedClassName);
        $pivotName = str_ireplace(self::MODEL_SUFFIX, '', $relatedBaseName);

        return $this->convertToSnakeCase($pivotName);
    }

    public function getModuleStatistics(): array
    {
        $modules = $this->getModules(false);

        return [
            'total_modules' => $this->countModulesRecursively($modules),
            'total_directories' => $this->countDirectoriesRecursively($modules),
            'cache_status' => Cache::tags([$this->cacheTag])->has($this->buildModuleCacheKey()),
            'models_path' => $this->modelsBasePath,
            'excluded_files' => $this->excludedModelFiles,
            'generated_at' => now()->toISOString(),
        ];
    }

    private function countModulesRecursively(array $modules): int
    {
        $count = 0;

        foreach ($modules as $module) {
            if (isset($module['type']) && $module['type'] === 'model') {
                $count++;
            }

            if (isset($module['children']) && is_array($module['children'])) {
                $count += $this->countModulesRecursively($module['children']);
            }
        }

        return $count;
    }

    private function countDirectoriesRecursively(array $modules): int
    {
        $count = 0;

        foreach ($modules as $module) {
            if (isset($module['type']) && $module['type'] === 'directory') {
                $count++;
            }

            if (isset($module['children']) && is_array($module['children'])) {
                $count += $this->countDirectoriesRecursively($module['children']);
            }
        }

        return $count;
    }
}
