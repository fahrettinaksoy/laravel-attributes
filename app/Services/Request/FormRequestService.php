<?php

declare(strict_types=1);

namespace App\Services\Request;

use App\Services\Module\ModuleRelationDetectorService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Throwable;

final class FormRequestService
{
    private const REQUEST_NAMESPACE_BASE = 'App\\Http\\Requests\\';

    private const PIVOT_NAMESPACE_SUFFIX = '\\Pivot\\';

    private const REQUEST_CLASS_SUFFIX = 'Request';

    private const PIVOT_ROUTE_PREFIX = 'pivot.';

    private const UNKNOWN_ACTION = 'unknown';

    private const ROUTE_PATH_PARAMETER = 'path';

    public function __construct(
        private readonly Container $container,
        private readonly LoggerInterface $logger,
        private readonly ModuleRelationDetectorService $moduleRelationDetectorService,
    ) {}

    public function resolve(Request $request, mixed $fallbackRequest = null): object
    {
        try {
            $currentAction = $this->getCurrentAction($request);

            $this->logRequestResolution($currentAction);

            $requestClassName = $this->determineRequestClassName($request, $currentAction);

            if ($this->isValidRequestClass($requestClassName)) {
                return $this->createAndValidateFormRequest($requestClassName, $request);
            }

            return $this->resolveFallbackRequest($fallbackRequest, $currentAction);

        } catch (Throwable $exception) {
            return $this->handleRequestResolutionException(
                $exception,
                $this->getCurrentAction($request)
            );
        }
    }

    public function validateNestedData(
        array $validatedData,
        string $currentAction,
        object $model,
        ?string $modelPath = null
    ): array {
        $modelPath = $modelPath ?? $this->determineModelPathFromRequest() ?? $this->getModelPathFromModel($model);

        $relations = $this->moduleRelationDetectorService->getHasManyRelations($model);

        foreach ($relations as $relationName) {
            if ($this->hasRelationData($validatedData, $relationName)) {
                $validatedData[$relationName] = $this->validateRelationData(
                    $relationName,
                    $validatedData[$relationName],
                    $currentAction,
                    $model,
                    $modelPath
                );
            }
        }

        return $validatedData;
    }

    private function determineRequestClassName(Request $request, string $action): ?string
    {
        return $this->isPivotRoute($request)
            ? $this->buildPivotRequestClassName($action, $request)
            : $this->buildModuleRequestClassName($action, $request);
    }

    private function buildPivotRequestClassName(string $action, Request $request): ?string
    {
        $pivotContext = $this->extractPivotContext($request);

        if (! $this->isValidPivotContext($pivotContext)) {
            $this->logWarning('Invalid pivot context for request resolution', [
                'action' => $action,
                'context' => $pivotContext,
            ]);

            return null;
        }

        return $this->constructPivotClassName($pivotContext, $action);
    }

    private function buildModuleRequestClassName(string $action, Request $request): ?string
    {
        $modelPath = $this->resolveModelPath($request);

        if (empty($modelPath)) {
            $this->logWarning('Could not resolve model path for request', ['action' => $action]);

            return null;
        }

        return $this->constructModuleClassName($modelPath, $action);
    }

    private function extractPivotContext(Request $request): array
    {
        return [
            'mainModelPath' => $request->attributes->get('mainModelPath'),
            'relationName' => $request->attributes->get('relationName'),
            'tableName' => $request->attributes->get('tableName'),
        ];
    }

    private function isValidPivotContext(array $context): bool
    {
        $requiredKeys = ['mainModelPath', 'relationName', 'tableName'];

        return collect($requiredKeys)->every(
            fn ($key) => ! empty($context[$key]) && is_string($context[$key]),
        );
    }

    private function constructPivotClassName(array $pivotContext, string $action): string
    {
        $namespaceSegments = $this->buildNamespaceSegments($pivotContext['mainModelPath']);
        $namespace = $this->buildPivotNamespace($namespaceSegments, $pivotContext);
        $className = $this->buildPivotClassName($pivotContext, $action);

        return $namespace.'\\'.$className;
    }

    private function constructModuleClassName(string $modelPath, string $action): string
    {
        $pathSegments = $this->splitPath($modelPath);
        $namespaceSegments = $this->buildNamespaceSegments($modelPath);
        $namespace = self::REQUEST_NAMESPACE_BASE.implode('\\', $namespaceSegments);
        $className = Str::studly(end($pathSegments)).Str::studly($action).self::REQUEST_CLASS_SUFFIX;

        return $namespace.'\\'.$className;
    }

    private function buildNamespaceSegments(string $modelPath): array
    {
        return collect($this->splitPath($modelPath))
            ->map(fn ($segment) => Str::studly($segment))
            ->toArray();
    }

    private function splitPath(string $path): array
    {
        return array_filter(explode('/', $path), fn ($segment) => ! empty($segment));
    }

    private function buildPivotNamespace(array $namespaceSegments, array $pivotContext): string
    {
        return self::REQUEST_NAMESPACE_BASE
            .implode('\\', $namespaceSegments)
            .self::PIVOT_NAMESPACE_SUFFIX
            .Str::studly($pivotContext['tableName'])
            .Str::studly($pivotContext['relationName']);
    }

    private function buildPivotClassName(array $pivotContext, string $action): string
    {
        return Str::studly($pivotContext['tableName'])
            .Str::studly($pivotContext['relationName'])
            .Str::studly($action)
            .self::REQUEST_CLASS_SUFFIX;
    }

    private function resolveModelPath(Request $request): ?string
    {
        $modelPath = $request->attributes->get('mainModelPath');
        if (! empty($modelPath) && is_string($modelPath)) {
            return $modelPath;
        }

        $routeParameter = $this->getCurrentRoute()?->parameter(self::ROUTE_PATH_PARAMETER);

        return is_string($routeParameter) ? $routeParameter : null;
    }

    private function getCurrentAction(Request $request): string
    {
        $currentRoute = $this->getCurrentRoute();

        if (! $currentRoute instanceof Route) {
            return self::UNKNOWN_ACTION;
        }

        $routeName = $currentRoute->getName();

        if ($this->isPivotRouteName($routeName)) {
            return $this->extractPivotAction($routeName);
        }

        return $this->extractActionFromRouteName($currentRoute->getActionName());
    }

    private function isPivotRoute(Request $request): bool
    {
        return $request->attributes->getBoolean('isPivotRoute');
    }

    private function isPivotRouteName(?string $routeName): bool
    {
        return is_string($routeName) && str_contains($routeName, self::PIVOT_ROUTE_PREFIX);
    }

    private function extractPivotAction(string $routeName): string
    {
        $actionWithPrefix = Str::afterLast($routeName, '.');

        return str_replace(self::PIVOT_ROUTE_PREFIX, '', $actionWithPrefix) ?: self::UNKNOWN_ACTION;
    }

    private function extractActionFromRouteName(?string $routeAction): string
    {
        if (! is_string($routeAction) || empty($routeAction)) {
            return self::UNKNOWN_ACTION;
        }

        $action = Str::afterLast($routeAction, '@');

        return ! empty($action) ? $action : self::UNKNOWN_ACTION;
    }

    private function isValidRequestClass(?string $className): bool
    {
        return is_string($className) && ! empty($className) && class_exists($className);
    }

    private function createAndValidateFormRequest(string $requestClassName, Request $currentRequest): object
    {
        $formRequest = $this->container->make($requestClassName);
        $this->populateFormRequest($formRequest, $currentRequest);
        $formRequest->validateResolved();

        return $formRequest;
    }

    private function populateFormRequest(object $formRequest, Request $currentRequest): void
    {
        if (method_exists($formRequest, 'validateResolved')) {
            $formRequest->query = $currentRequest->query;
            $formRequest->request = $currentRequest->request;
            $formRequest->attributes = $currentRequest->attributes;
            $formRequest->cookies = $currentRequest->cookies;
            $formRequest->files = $currentRequest->files;
            $formRequest->server = $currentRequest->server;
            $formRequest->headers = $currentRequest->headers;
        }
    }

    private function resolveFallbackRequest(mixed $fallbackRequest, string $currentAction): object
    {
        if (is_object($fallbackRequest)) {
            return $fallbackRequest;
        }

        if (is_string($fallbackRequest) && class_exists($fallbackRequest)) {
            return $this->container->make($fallbackRequest);
        }

        throw new InvalidArgumentException(
            sprintf('No valid request class could be resolved for action: %s', $currentAction),
        );
    }

    private function handleRequestResolutionException(Throwable $exception, string $action): object
    {
        if ($exception instanceof ValidationException) {
            throw $exception;
        }

        $this->logError('Form request resolution failed', [
            'action' => $action,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        throw new InvalidArgumentException(
            'Failed to resolve form request: '.$exception->getMessage(),
            previous: $exception,
        );
    }

    private function validateRelationData(
        string $relationName,
        array $relationData,
        string $currentAction,
        object $model,
        string $modelPath
    ): array {
        $validatedItems = [];

        foreach ($relationData as $index => $item) {
            $requestClass = $this->determineRelationRequestClass(
                $relationName,
                $currentAction,
                $model,
                $modelPath
            );

            if ($requestClass && class_exists($requestClass)) {
                try {
                    $validatedItems[] = $this->validateSingleItem($requestClass, $item);
                } catch (ValidationException $e) {
                    throw $this->addIndexToValidationErrors($e, $relationName, $index);
                }
            } else {
                $validatedItems[] = $item;
            }
        }

        return $validatedItems;
    }

    private function cleanItemDataForValidation(array $itemData, string $requestClass): array
    {
        if (! class_exists($requestClass)) {
            return $itemData;
        }

        $tempFormRequest = $this->container->make($requestClass);
        $rules = method_exists($tempFormRequest, 'rules') ? $tempFormRequest->rules() : [];

        return collect($itemData)->only(array_keys($rules))->toArray();
    }

    private function validateSingleItem(string $requestClass, array $itemData): array
    {
        $cleanedData = $this->cleanItemDataForValidation($itemData, $requestClass);
        $formRequest = $this->container->make($requestClass);
        $formRequest->merge($cleanedData);
        $formRequest->setMethod('POST');
        $formRequest->setRouteResolver(fn () => request()->route());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    private function determineRelationRequestClass(
        string $relationName,
        string $currentAction,
        object $model,
        string $modelPath
    ): ?string {
        if (empty($modelPath)) {
            return null;
        }

        $namespaceSegments = $this->buildNamespaceSegments($modelPath);
        $pivotTableName = $this->getPivotTableName($relationName, $model);

        $namespace = self::REQUEST_NAMESPACE_BASE
            .implode('\\', $namespaceSegments)
            .self::PIVOT_NAMESPACE_SUFFIX
            .Str::studly($pivotTableName)
            .Str::studly($relationName);

        $className = Str::studly($pivotTableName)
            .Str::studly($relationName)
            .Str::studly($currentAction)
            .self::REQUEST_CLASS_SUFFIX;

        return $namespace.'\\'.$className;
    }

    private function getModelPathFromModel(object $model): string
    {
        $modelClass = get_class($model);
        $modelName = class_basename($modelClass);
        $cleanName = str_replace('Model', '', $modelName);

        $namespaceParts = explode('\\', $modelClass);
        array_shift($namespaceParts); // Remove 'App'
        array_shift($namespaceParts); // Remove 'Models'
        array_pop($namespaceParts);   // Remove model name

        $path = strtolower(implode('/', $namespaceParts));

        return ! empty($path) ? $path.'/'.strtolower($cleanName) : strtolower($cleanName);
    }

    private function getPivotTableName(string $relationName, object $model): string
    {
        $modelName = class_basename(get_class($model));
        $cleanModelName = str_replace('Model', '', $modelName);

        return $cleanModelName.Str::studly($relationName);
    }

    private function addIndexToValidationErrors(
        ValidationException $exception,
        string $relationName,
        int $index
    ): ValidationException {
        $errors = collect($exception->errors())
            ->mapWithKeys(fn ($messages, $field) => [
                "{$relationName}.{$index}.{$field}" => $messages,
            ])
            ->toArray();

        return ValidationException::withMessages($errors);
    }

    private function hasRelationData(array $data, string $relationName): bool
    {
        return isset($data[$relationName]) && is_array($data[$relationName]);
    }

    private function determineModelPathFromRequest(): ?string
    {
        $request = request();

        return $request->attributes->get('mainModelPath') ?? $request->route('path');
    }

    private function getCurrentRoute(): ?Route
    {
        return RouteFacade::current();
    }

    private function logRequestResolution(string $action): void
    {
        $this->logger->debug('Attempting to resolve form request', [
            'action' => $action,
            'route_name' => $this->getCurrentRoute()?->getName(),
        ]);
    }

    private function logWarning(string $message, array $context = []): void
    {
        $this->logger->warning($message, $context);
    }

    private function logError(string $message, array $context = []): void
    {
        $this->logger->error($message, $context);
    }
}
