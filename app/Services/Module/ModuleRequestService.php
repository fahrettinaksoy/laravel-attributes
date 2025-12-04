<?php

declare(strict_types=1);

namespace App\Services\Module;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Unified service for module-related operations.
 *
 * Responsibilities:
 * - FormRequest resolution and validation
 * - Nested relation validation
 * - HasMany relation detection
 *
 * Combines functionality from:
 * - FormRequestService (request resolution)
 * - ModuleRelationDetectorService (relation detection)
 * - ModuleScannerService (module scanning)
 *
 * @package App\Services\Module
 */
class ModuleRequestService
{
    /**
     * Cache for detected HasMany relations per model class.
     *
     * @var array<string, array<string>>
     */
    private array $relationCache = [];

    /**
     * Default action name when route action cannot be determined.
     */
    private const DEFAULT_ACTION = 'unknown';

    /**
     * Pivot route name prefix for detection.
     */
    private const PIVOT_ROUTE_PREFIX = 'pivot.';

    public function __construct(
        private readonly Container $container,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    // ==================== FORM REQUEST RESOLUTION ====================

    /**
     * Resolve and validate FormRequest for current request.
     *
     * Automatically detects:
     * - Standard module requests (VehiclesStoreRequest)
     * - Pivot requests (VehiclesFeaturesPivotStoreRequest)
     *
     * @param Request $request Current HTTP request
     * @param mixed $fallback Fallback request class or instance
     * @return object Validated FormRequest instance
     * @throws ValidationException When validation fails
     * @throws InvalidArgumentException When no valid request class found
     *
     * @example
     * $validated = $service->resolveFormRequest($request);
     * // Returns: VehiclesStoreRequest instance (validated)
     */
    public function resolveFormRequest(Request $request, mixed $fallback = null): object
    {
        try {
            $action = $this->extractAction($request);

            $this->logDebug('Resolving FormRequest', [
                'action' => $action,
                'route' => $request->route()?->getName(),
                'is_pivot' => $request->attributes->getBoolean('isPivotRoute'),
            ]);

            $requestClass = $this->buildRequestClass($request, $action);

            if ($this->isValidClass($requestClass)) {
                return $this->createAndValidateFormRequest($requestClass, $request);
            }

            $this->logWarning('FormRequest class not found, using fallback', [
                'attempted_class' => $requestClass,
                'action' => $action,
            ]);

            return $this->resolveFallback($fallback, $action);

        } catch (ValidationException $e) {
            // Re-throw validation exceptions as-is
            throw $e;
        } catch (Throwable $e) {
            $this->logError('FormRequest resolution failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw new InvalidArgumentException(
                'Failed to resolve FormRequest: ' . $e->getMessage(),
                previous: $e
            );
        }
    }

    /**
     * Validate nested relationship data using their respective FormRequests.
     *
     * Automatically detects HasMany relations and validates each item
     * using the corresponding pivot FormRequest class.
     *
     * @param array $data Validated data containing relation arrays
     * @param string $action Current action name ('store', 'update', etc.)
     * @param object $model Parent model instance
     * @param string|null $modelPath Optional model path override
     * @return array Data with validated nested relations
     * @throws ValidationException When relation validation fails
     *
     * @example
     * $data = $service->validateNestedRelations(
     *     $request->validated(),
     *     'store',
     *     $vehicleModel
     * );
     * // Validates 'features' array using VehiclesFeaturesPivotStoreRequest
     */
    public function validateNestedRelations(
        array $data,
        string $action,
        object $model,
        ?string $modelPath = null
    ): array {
        // Resolve model path
        $modelPath = $modelPath ?? ModulePathResolver::extractPathFromModelClass(get_class($model));

        // Detect HasMany relations
        $relations = $this->getHasManyRelations($model);

        if (empty($relations)) {
            return $data;
        }

        $this->logDebug('Validating nested relations', [
            'model' => get_class($model),
            'relations' => $relations,
            'action' => $action,
        ]);

        // Validate each relation
        foreach ($relations as $relationName) {
            if ($this->hasRelationData($data, $relationName)) {
                $data[$relationName] = $this->validateRelationItems(
                    $relationName,
                    $data[$relationName],
                    $action,
                    $modelPath
                );
            }
        }

        return $data;
    }

    // ==================== RELATION DETECTION ====================

    /**
     * Get all HasMany relation names for a given model.
     *
     * Uses reflection to detect public methods that return HasMany instances.
     * Results are cached per model class for performance.
     *
     * @param object $model Model instance
     * @return array<string> Array of relation method names
     *
     * @example
     * $relations = $service->getHasManyRelations($vehicleModel);
     * // Returns: ['features', 'colors', 'images']
     */
    public function getHasManyRelations(object $model): array
    {
        $class = get_class($model);

        if (!isset($this->relationCache[$class])) {
            $this->relationCache[$class] = $this->detectHasManyRelations($model);
        }

        return $this->relationCache[$class];
    }

    /**
     * Detect HasMany relations using reflection.
     *
     * @param object $model Model instance
     * @return array<string> Relation method names
     */
    private function detectHasManyRelations(object $model): array
    {
        $relations = [];
        $reflection = new ReflectionClass($model);
        $modelClass = get_class($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods and methods with parameters
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $result = $method->invoke($model);

                if ($result instanceof HasMany) {
                    $relations[] = $method->getName();
                }
            } catch (Throwable $e) {
                // Skip methods that throw exceptions
                $this->logDebug('Skipped method during relation detection', [
                    'method' => $method->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $relations;
    }

    /**
     * Clear relation detection cache.
     * Useful for testing or when models change at runtime.
     *
     * @param string|null $modelClass Optional specific model class to clear
     * @return void
     */
    public function clearRelationCache(?string $modelClass = null): void
    {
        if ($modelClass === null) {
            $this->relationCache = [];
        } else {
            unset($this->relationCache[$modelClass]);
        }
    }

    // ==================== PRIVATE HELPERS - REQUEST BUILDING ====================

    /**
     * Build FormRequest class name based on request type (pivot or main).
     *
     * @param Request $request Current request
     * @param string $action Action name
     * @return string|null Fully qualified class name
     */
    private function buildRequestClass(Request $request, string $action): ?string
    {
        $isPivot = $request->attributes->getBoolean('isPivotRoute');

        return $isPivot
            ? $this->buildPivotRequestClass($request, $action)
            : $this->buildMainRequestClass($request, $action);
    }

    /**
     * Build pivot FormRequest class name.
     *
     * @param Request $request Current request
     * @param string $action Action name
     * @return string|null Class name or null if required attributes missing
     */
    private function buildPivotRequestClass(Request $request, string $action): ?string
    {
        $mainPath = $request->attributes->get('mainModelPath');
        $relationName = $request->attributes->get('relationName');

        if (!$mainPath || !$relationName) {
            $this->logWarning('Missing pivot context for request resolution', [
                'mainPath' => $mainPath,
                'relationName' => $relationName,
            ]);
            return null;
        }

        return ModulePathResolver::buildPivotRequestClass($mainPath, $relationName, $action);
    }

    /**
     * Build main module FormRequest class name.
     *
     * @param Request $request Current request
     * @param string $action Action name
     * @return string|null Class name or null if model path missing
     */
    private function buildMainRequestClass(Request $request, string $action): ?string
    {
        $modelPath = $request->attributes->get('mainModelPath');

        if (!$modelPath) {
            $this->logWarning('Model path not found in request attributes');
            return null;
        }

        return ModulePathResolver::buildRequestClass($modelPath, $action);
    }

    // ==================== PRIVATE HELPERS - REQUEST VALIDATION ====================

    /**
     * Create and validate FormRequest instance.
     *
     * @param string $requestClass FormRequest class name
     * @param Request $currentRequest Current HTTP request
     * @return object Validated FormRequest instance
     * @throws ValidationException When validation fails
     */
    private function createAndValidateFormRequest(string $requestClass, Request $currentRequest): object
    {
        // Create FormRequest instance
        $formRequest = $this->container->make($requestClass);

        // Copy request data to FormRequest
        $this->populateFormRequest($formRequest, $currentRequest);

        // Validate
        $formRequest->validateResolved();

        $this->logDebug('FormRequest validated successfully', [
            'class' => $requestClass,
        ]);

        return $formRequest;
    }

    /**
     * Populate FormRequest with data from current request.
     *
     * @param object $formRequest FormRequest instance
     * @param Request $currentRequest Current HTTP request
     * @return void
     */
    private function populateFormRequest(object $formRequest, Request $currentRequest): void
    {
        if (!method_exists($formRequest, 'validateResolved')) {
            return;
        }

        $formRequest->query = $currentRequest->query;
        $formRequest->request = $currentRequest->request;
        $formRequest->attributes = $currentRequest->attributes;
        $formRequest->cookies = $currentRequest->cookies;
        $formRequest->files = $currentRequest->files;
        $formRequest->server = $currentRequest->server;
        $formRequest->headers = $currentRequest->headers;
    }

    /**
     * Validate array of relation items.
     *
     * @param string $relationName Relation method name
     * @param array $items Array of relation data
     * @param string $action Action name
     * @param string $modelPath Parent model path
     * @return array Validated items
     * @throws ValidationException When validation fails
     */
    private function validateRelationItems(
        string $relationName,
        array $items,
        string $action,
        string $modelPath
    ): array {
        $validated = [];
        $requestClass = ModulePathResolver::buildPivotRequestClass($modelPath, $relationName, $action);

        if (!$this->isValidClass($requestClass)) {
            $this->logDebug('Pivot request class not found, skipping validation', [
                'relation' => $relationName,
                'class' => $requestClass,
            ]);
            return $items;
        }

        foreach ($items as $index => $item) {
            try {
                $validated[] = $this->validateSingleItem($requestClass, $item);
            } catch (ValidationException $e) {
                throw $this->prefixValidationErrors($e, $relationName, $index);
            }
        }

        return $validated;
    }

    /**
     * Validate single relation item.
     *
     * @param string $requestClass FormRequest class name
     * @param array $itemData Item data to validate
     * @return array Validated data
     * @throws ValidationException When validation fails
     */
    private function validateSingleItem(string $requestClass, array $itemData): array
    {
        $formRequest = $this->container->make($requestClass);
        $formRequest->merge($itemData);
        $formRequest->setMethod('POST');
        $formRequest->setRouteResolver(fn() => request()->route());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    /**
     * Prefix validation errors with relation name and index.
     *
     * Converts:
     * - 'name' => ['required']
     *
     * To:
     * - 'features.0.name' => ['required']
     *
     * @param ValidationException $exception Original validation exception
     * @param string $relationName Relation name
     * @param int $index Item index
     * @return ValidationException New exception with prefixed errors
     */
    private function prefixValidationErrors(
        ValidationException $exception,
        string $relationName,
        int $index
    ): ValidationException {
        $errors = collect($exception->errors())
            ->mapWithKeys(fn($messages, $field) => [
                "{$relationName}.{$index}.{$field}" => $messages
            ])
            ->toArray();

        return ValidationException::withMessages($errors);
    }

    // ==================== PRIVATE HELPERS - ACTION EXTRACTION ====================

    /**
     * Extract action name from request.
     *
     * Supports:
     * - Pivot routes (route name: 'pivot.store')
     * - Standard routes (action: 'VehiclesController@store')
     *
     * @param Request $request Current request
     * @return string Action name
     */
    private function extractAction(Request $request): string
    {
        $route = $request->route();

        if (!$route) {
            return self::DEFAULT_ACTION;
        }

        $routeName = $route->getName();

        // Check for pivot route
        if ($this->isPivotRouteName($routeName)) {
            return $this->extractPivotAction($routeName);
        }

        // Extract from controller action
        return $this->extractControllerAction($route->getActionName());
    }

    /**
     * Check if route name is a pivot route.
     *
     * @param string|null $routeName Route name
     * @return bool True if pivot route
     */
    private function isPivotRouteName(?string $routeName): bool
    {
        return is_string($routeName) && str_contains($routeName, self::PIVOT_ROUTE_PREFIX);
    }

    /**
     * Extract action from pivot route name.
     *
     * Example: 'vehicles.pivot.features.store' -> 'store'
     *
     * @param string $routeName Route name
     * @return string Action name
     */
    private function extractPivotAction(string $routeName): string
    {
        $action = Str::afterLast($routeName, '.');
        return !empty($action) ? $action : self::DEFAULT_ACTION;
    }

    /**
     * Extract action from controller action string.
     *
     * Example: 'App\Http\Controllers\VehiclesController@store' -> 'store'
     *
     * @param string|null $actionName Controller action string
     * @return string Action name
     */
    private function extractControllerAction(?string $actionName): string
    {
        if (!is_string($actionName) || empty($actionName)) {
            return self::DEFAULT_ACTION;
        }

        $action = Str::afterLast($actionName, '@');
        return !empty($action) ? $action : self::DEFAULT_ACTION;
    }

    // ==================== PRIVATE HELPERS - VALIDATION ====================

    /**
     * Check if class name is valid and exists.
     *
     * @param string|null $className Class name
     * @return bool True if valid
     */
    private function isValidClass(?string $className): bool
    {
        return is_string($className) && !empty($className) && class_exists($className);
    }

    /**
     * Check if data array contains relation data.
     *
     * @param array $data Data array
     * @param string $relationName Relation name
     * @return bool True if relation data exists
     */
    private function hasRelationData(array $data, string $relationName): bool
    {
        return isset($data[$relationName]) && is_array($data[$relationName]);
    }

    /**
     * Resolve fallback request.
     *
     * @param mixed $fallback Fallback class name or instance
     * @param string $action Current action (for error message)
     * @return object Request instance
     * @throws InvalidArgumentException When fallback invalid
     */
    private function resolveFallback(mixed $fallback, string $action): object
    {
        if (is_object($fallback)) {
            return $fallback;
        }

        if (is_string($fallback) && class_exists($fallback)) {
            return $this->container->make($fallback);
        }

        throw new InvalidArgumentException(
            sprintf('No valid FormRequest class found for action: %s', $action)
        );
    }

    // ==================== LOGGING ====================

    /**
     * Log debug message.
     *
     * @param string $message Message
     * @param array $context Context data
     * @return void
     */
    private function logDebug(string $message, array $context = []): void
    {
        $this->logger?->debug($message, $context);
    }

    /**
     * Log warning message.
     *
     * @param string $message Message
     * @param array $context Context data
     * @return void
     */
    private function logWarning(string $message, array $context = []): void
    {
        $this->logger?->warning($message, $context);
    }

    /**
     * Log error message.
     *
     * @param string $message Message
     * @param array $context Context data
     * @return void
     */
    private function logError(string $message, array $context = []): void
    {
        $this->logger?->error($message, $context);
    }

    // ==================== PUBLIC UTILITIES ====================

    /**
     * Get service statistics.
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        return [
            'cached_models' => count($this->relationCache),
            'total_relations' => array_sum(array_map('count', $this->relationCache)),
        ];
    }
}
