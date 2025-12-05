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

class ModuleRequestService
{
    private array $relationCache = [];

    private const ACTION_DEFAULT = 'unknown';

    private const PIVOT_PREFIX = 'pivot.';

    public function __construct(
        private readonly Container $container,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    public function resolveFormRequest(Request $request, mixed $fallback = null): object
    {
        try {
            $action = $this->action($request);

            $this->log('debug', 'Resolving FormRequest', [
                'action' => $action,
                'route' => $request->route()?->getName(),
                'is_pivot' => $request->attributes->getBoolean('isPivotRoute'),
            ]);

            if ($class = $this->requestClass($request, $action)) {
                return $this->validate($class, $request);
            }

            $this->log('info', 'FormRequest not found, using fallback', [
                'action' => $action,
            ]);

            return $this->fallback($fallback, $action);

        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            $this->log('error', 'FormRequest resolution failed', [
                'error' => $e->getMessage(),
            ]);

            throw new InvalidArgumentException(
                'Failed to resolve FormRequest: '.$e->getMessage(),
                previous: $e
            );
        }
    }

    public function validateNestedRelations(array $data, string $action, object $model, ?string $modelPath = null): array
    {
        $modelPath ??= ModulePathResolver::extractPathFromModelClass(get_class($model));
        $relations = $this->getHasManyRelations($model);

        if (empty($relations)) {
            return $data;
        }

        $this->log('debug', 'Validating nested relations', [
            'model' => get_class($model),
            'relations' => $relations,
            'action' => $action,
        ]);

        foreach ($relations as $relation) {
            if (isset($data[$relation]) && is_array($data[$relation])) {
                $data[$relation] = $this->validateItems($relation, $data[$relation], $action, $modelPath);
            }
        }

        return $data;
    }

    public function getHasManyRelations(object $model): array
    {
        $class = get_class($model);

        return $this->relationCache[$class] ??= $this->detectRelations($model);
    }

    private function detectRelations(object $model): array
    {
        $relations = [];
        $reflection = new ReflectionClass($model);
        $modelClass = get_class($model);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $modelClass || $method->getNumberOfParameters() > 0) {
                continue;
            }

            try {
                if ($method->invoke($model) instanceof HasMany) {
                    $relations[] = $method->getName();
                }
            } catch (Throwable) {

            }
        }

        return $relations;
    }

    private function requestClass(Request $request, string $action): ?string
    {
        $class = $request->attributes->getBoolean('isPivotRoute') ? $this->pivotClass($request, $action) : $this->mainClass($request, $action);

        if ($class && class_exists($class)) {
            return $class;
        }

        return null;
    }

    private function pivotClass(Request $request, string $action): ?string
    {
        $path = $request->attributes->get('mainModelPath');
        $relation = $request->attributes->get('relationName');

        if (! $path || ! $relation) {
            $this->log('warning', 'Missing pivot context', compact('path', 'relation'));

            return null;
        }

        return ModulePathResolver::buildPivotRequestClass($path, $relation, $action);
    }

    private function mainClass(Request $request, string $action): ?string
    {
        if (! $path = $request->attributes->get('mainModelPath')) {
            $this->log('warning', 'Model path not found');

            return null;
        }

        return ModulePathResolver::buildRequestClass($path, $action);
    }

    private function validate(string $class, Request $current): object
    {
        $form = $this->container->make($class);

        if (method_exists($form, 'validateResolved')) {
            $this->populate($form, $current);
            $form->validateResolved();
        }

        $this->log('debug', 'FormRequest validated', ['class' => $class]);

        return $form;
    }

    private function populate(object $form, Request $current): void
    {
        $form->query = $current->query;
        $form->request = $current->request;
        $form->attributes = $current->attributes;
        $form->cookies = $current->cookies;
        $form->files = $current->files;
        $form->server = $current->server;
        $form->headers = $current->headers;
    }

    private function validateItems(string $relation, array $items, string $action, string $modelPath): array
    {
        $class = ModulePathResolver::buildPivotRequestClass($modelPath, $relation, $action);

        if (! class_exists($class)) {
            $this->log('debug', 'Pivot request not found, skipping', [
                'relation' => $relation,
                'class' => $class,
            ]);

            return $items;
        }

        $validated = [];

        foreach ($items as $index => $item) {
            try {
                $validated[] = $this->validateItem($class, $item);
            } catch (ValidationException $e) {
                throw $this->prefixErrors($e, $relation, $index);
            }
        }

        return $validated;
    }

    private function validateItem(string $class, array $data): array
    {
        $form = $this->container->make($class);
        $form->merge($data);
        $form->setMethod('POST');
        $form->setRouteResolver(fn () => request()->route());
        $form->validateResolved();

        return $form->validated();
    }

    private function prefixErrors(ValidationException $e, string $relation, int $index): ValidationException
    {
        $errors = collect($e->errors())
            ->mapWithKeys(fn ($msg, $field) => [
                "{$relation}.{$index}.{$field}" => $msg,
            ])
            ->all();

        return ValidationException::withMessages($errors);
    }

    private function action(Request $request): string
    {
        if (! $route = $request->route()) {
            return self::ACTION_DEFAULT;
        }

        $name = $route->getName();

        if ($name && str_contains($name, self::PIVOT_PREFIX)) {
            return Str::afterLast($name, '.') ?: self::ACTION_DEFAULT;
        }

        $action = Str::afterLast($route->getActionName() ?: '', '@');

        return $action ?: self::ACTION_DEFAULT;
    }

    private function fallback(mixed $fallback, string $action): object
    {
        if (is_object($fallback)) {
            return $fallback;
        }

        if (is_string($fallback) && class_exists($fallback)) {
            return $this->container->make($fallback);
        }

        throw new InvalidArgumentException(
            "No valid FormRequest for action: {$action}"
        );
    }

    private function log(string $level, string $message, array $context = []): void
    {
        $this->logger?->{$level}($message, $context);
    }
}
