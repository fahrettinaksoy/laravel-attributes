<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait NestedValidationTrait
{
    use HasManyRelationDetector;

    protected ?string $modelPath = null;

    protected function validateNestedData(array $validatedData, string $currentAction): array
    {
        if (! method_exists($this, 'model') && ! isset($this->model)) {
            return $validatedData;
        }

        $model = $this->model ?? $this->getModel();
        $this->modelPath = $this->modelPath ?? $this->determineModelPathFromRequest() ?? $this->getNestedModelPath($model);
        $relations = $this->getHasManyRelationMethods($model);

        foreach ($relations as $relationName) {
            if (isset($validatedData[$relationName]) && is_array($validatedData[$relationName])) {
                $validatedData[$relationName] = $this->validateRelationData(
                    $relationName,
                    $validatedData[$relationName],
                    $currentAction,
                );
            }
        }

        return $validatedData;
    }

    protected function determineModelPathFromRequest(): ?string
    {
        $request = request();

        $modelPath = $request->attributes->get('mainModelPath');
        if ($modelPath) {
            return $modelPath;
        }

        $routePath = $request->route('path');
        if ($routePath) {
            return $routePath;
        }

        return null;
    }

    protected function validateRelationData(string $relationName, array $relationData, string $currentAction): array
    {
        $validatedItems = [];

        foreach ($relationData as $index => $item) {
            $requestClass = $this->determineRelationRequestClass($relationName, $currentAction);

            if ($requestClass && class_exists($requestClass)) {
                try {
                    $validatedItems[] = $this->validateSingleItem($requestClass, $item, $index);
                } catch (ValidationException $e) {
                    throw $this->addIndexToValidationErrors($e, $relationName, $index);
                }
            } else {
                $validatedItems[] = $item;
            }
        }

        return $validatedItems;
    }

    protected function validateSingleItem(string $requestClass, array $itemData, int $index): array
    {
        $container = app();
        $formRequest = $container->make($requestClass);
        $formRequest->merge($itemData);
        $formRequest->setMethod('POST');
        $formRequest->setRouteResolver(function () {
            return request()->route();
        });

        $formRequest->validateResolved();

        return $formRequest->validated();
    }

    protected function determineRelationRequestClass(string $relationName, string $currentAction): ?string
    {
        $model = $this->model ?? $this->getModel();
        $modelPath = $this->modelPath ?? $this->getNestedModelPath($model);

        if (empty($modelPath)) {
            return null;
        }

        $namespaceSegments = $this->buildNestedNamespaceSegments($modelPath);
        $namespace = 'App\\Http\\Requests\\'.implode('\\', $namespaceSegments).'\\Pivot\\'.Str::studly($this->getPivotTableName($relationName)).Str::studly($relationName);
        $className = Str::studly($this->getPivotTableName($relationName)).Str::studly($relationName).Str::studly($currentAction).'Request';
        return $namespace.'\\'.$className;
    }

    protected function getNestedModelPath($model): string
    {
        $modelClass = get_class($model);
        $modelName = class_basename($modelClass);
        $cleanName = str_replace('Model', '', $modelName);
        $namespaceParts = explode('\\', $modelClass);

        array_shift($namespaceParts);
        array_shift($namespaceParts);
        array_pop($namespaceParts);

        $path = strtolower(implode('/', $namespaceParts));
        if (! empty($path)) {
            $path .= '/'.strtolower($cleanName);
        } else {
            $path = strtolower($cleanName);
        }

        return $path;
    }

    protected function getPivotTableName(string $relationName): string
    {
        $model = $this->model ?? $this->getModel();
        $modelName = class_basename(get_class($model));
        $cleanModelName = str_replace('Model', '', $modelName);

        return $cleanModelName.Str::studly($relationName);
    }

    protected function buildNestedNamespaceSegments(string $modelPath): array
    {
        $pathSegments = $this->splitNestedPath($modelPath);

        return array_map(fn ($segment) => Str::studly($segment), $pathSegments);
    }

    protected function splitNestedPath(string $path): array
    {
        return array_filter(explode('/', $path), static fn (string $segment): bool => $segment !== '');
    }

    protected function addIndexToValidationErrors(ValidationException $exception, string $relationName, int $index): ValidationException
    {
        $errors = $exception->errors();
        $newErrors = [];

        foreach ($errors as $field => $messages) {
            $newField = "{$relationName}.{$index}.{$field}";
            $newErrors[$newField] = $messages;
        }

        return ValidationException::withMessages($newErrors);
    }
}
