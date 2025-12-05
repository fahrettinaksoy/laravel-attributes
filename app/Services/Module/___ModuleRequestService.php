<?php

declare(strict_types=1);

namespace App\Services\Module;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionMethod;
use Throwable;

/**
 * Handles FormRequest resolution and nested relation validation.
 */
final class ModuleRequestService
{
    private array $relationCache = [];

    public function __construct(
        private readonly Container $container,
    ) {}

    // ==================== PUBLIC API ====================

    public function resolveFormRequest(Request $request, mixed $fallback = null): object
    {
        try {
            $action = $this->getAction($request);
            $className = $this->buildRequestClass($request, $action);

            if ($className && class_exists($className)) {
                return $this->createFormRequest($className, $request);
            }

            return $this->resolveFallback($fallback, $action);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                'Failed to resolve form request: '.$e->getMessage(),
                previous: $e
            );
        }
    }

    public function validateNestedRelations(
        array $data,
        string $action,
        object $model,
        ?string $modelPath = null
    ): array {
        $modelPath = $modelPath ?? $this->getModelPath($model);
        $relations = $this->getHasManyRelations($model);

        foreach ($relations as $relation) {
            if (isset($data[$relation]) && is_array($data[$relation])) {
                $data[$relation] = $this->validateRelation(
                    $relation,
                    $data[$relation],
                    $action,
                    $modelPath
                );
            }
        }

        return $data;
    }

    public function getHasManyRelations(object $model): array
    {
        $class = get_class($model);

        return $this->relationCache[$class] ??= $this->detectRelations($model);
    }

    public function clearCache(): void
    {
        $this->relationCache = [];
    }

    // ==================== RELATION DETECTION ====================

    private function detectRelations(object $model): array
    {
        $relations = [];
        $reflect = new ReflectionClass($model);
        $class = get_class($model);

        foreach ($reflect->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $class || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                $result = $method->invoke($model);
                if ($result instanceof HasMany) {
                    $relations[] = $method->getName();
                }
            } catch (Throwable) {
                // Skip
            }
        }

        return $relations;
    }

    // ==================== REQUEST CLASS BUILDING ====================

    private function buildRequestClass(Request $request, string $action): ?string
    {
        return $request->attributes->getBoolean('isPivotRoute')
            ? $this->buildPivotClass($request, $action)
            : $this->buildMainClass($request, $action);
    }

    private function buildPivotClass(Request $request, string $action): ?string
    {
        $mainPath = $request->attributes->get('mainModelPath');
        $relation = $request->attributes->get('relationName');
        $table = $request->attributes->get('tableName');

        if (! $mainPath || ! $relation || ! $table) {
            return null;
        }

        $parts = array_map([Str::class, 'studly'], explode('/', $mainPath));
        $namespace = 'App\\Http\\Requests\\'.implode('\\', $parts)
            .'\\Pivot\\'.Str::studly($table).Str::studly($relation);

        $class = Str::studly($table).Str::studly($relation)
            .Str::studly($action).'Request';

        return $namespace.'\\'.$class;
    }

    private function buildMainClass(Request $request, string $action): ?string
    {
        $modelPath = $request->attributes->get('mainModelPath');

        if (! $modelPath) {
            return null;
        }

        $segments = explode('/', $modelPath);
        $parts = array_map([Str::class, 'studly'], $segments);
        $namespace = 'App\\Http\\Requests\\'.implode('\\', $parts);
        $class = Str::studly(end($segments)).Str::studly($action).'Request';

        return $namespace.'\\'.$class;
    }

    // ==================== ACTION EXTRACTION ====================

    private function getAction(Request $request): string
    {
        $route = $request->route();

        if (! $route) {
            return 'unknown';
        }

        $routeName = $route->getName();

        // Pivot route (e.g., pivot.store)
        if ($routeName && str_contains($routeName, 'pivot.')) {
            return Str::afterLast($routeName, '.');
        }

        // Standard route (e.g., Controller@store)
        $action = $route->getActionName();

        return Str::afterLast($action, '@') ?: 'unknown';
    }

    // ==================== FORM REQUEST CREATION ====================

    private function createFormRequest(string $className, Request $current): object
    {
        $formRequest = $this->container->make($className);

        // Copy request data
        if (method_exists($formRequest, 'validateResolved')) {
            $formRequest->query = $current->query;
            $formRequest->request = $current->request;
            $formRequest->attributes = $current->attributes;
            $formRequest->files = $current->files;
        }

        $formRequest->validateResolved();

        return $formRequest;
    }

    // ==================== NESTED VALIDATION ====================

    private function validateRelation(
        string $relation,
        array $items,
        string $action,
        string $modelPath
    ): array {
        $validated = [];
        $requestClass = $this->buildRelationClass($relation, $action, $modelPath);

        foreach ($items as $index => $item) {
            if ($requestClass && class_exists($requestClass)) {
                try {
                    $validated[] = $this->validateItem($requestClass, $item);
                } catch (ValidationException $e) {
                    throw $this->prefixErrors($e, $relation, $index);
                }
            } else {
                $validated[] = $item;
            }
        }

        return $validated;
    }

    private function buildRelationClass(
        string $relation,
        string $action,
        string $modelPath
    ): ?string {
        $segments = explode('/', $modelPath);
        $table = end($segments);

        $parts = array_map([Str::class, 'studly'], $segments);
        $namespace = 'App\\Http\\Requests\\'.implode('\\', $parts)
            .'\\Pivot\\'.Str::studly($table).Str::studly($relation);

        $class = Str::studly($table).Str::studly($relation)
            .Str::studly($action).'Request';

        return $namespace.'\\'.$class;
    }

    private function validateItem(string $requestClass, array $data): array
    {
        $formRequest = $this->container->make($requestClass);
        $formRequest->merge($data);
        $formRequest->setMethod('POST');
        $formRequest->setRouteResolver(fn () => request()->route());
        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    private function prefixErrors(
        ValidationException $e,
        string $relation,
        int $index
    ): ValidationException {
        $errors = collect($e->errors())
            ->mapWithKeys(fn ($msgs, $field) => [
                "{$relation}.{$index}.{$field}" => $msgs,
            ])
            ->toArray();

        return ValidationException::withMessages($errors);
    }

    // ==================== HELPERS ====================

    private function getModelPath(object $model): string
    {
        $class = get_class($model);
        $name = str_replace('Model', '', class_basename($class));

        $parts = explode('\\', $class);
        array_shift($parts); // Remove 'App'
        array_shift($parts); // Remove 'Models'
        array_pop($parts);   // Remove class name

        $path = strtolower(implode('/', $parts));

        return $path ? $path.'/'.strtolower($name) : strtolower($name);
    }

    private function resolveFallback(mixed $fallback, string $action): object
    {
        if (is_object($fallback)) {
            return $fallback;
        }

        if (is_string($fallback) && class_exists($fallback)) {
            return $this->container->make($fallback);
        }

        throw new InvalidArgumentException(
            "No valid request class for action: {$action}"
        );
    }
}
