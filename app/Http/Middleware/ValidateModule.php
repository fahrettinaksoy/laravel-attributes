<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ValidateModule
{
    private array $modelClassCache = [];

    private const CACHE_TTL = 3600;

    private const EXCEPTION_ROUTES = [];

    public function handle(Request $request, Closure $next): mixed
    {
        $segments = $request->segments();

        if (count($segments) < 3) {
            return $next($request);
        }

        $pathAfterApi = implode('/', array_slice($segments, 2));
        if ($this->isExceptionRoute($pathAfterApi)) {
            return $next($request);
        }

        $cacheKey = 'model_resolution_'.md5(implode('/', $segments));
        if (isset($this->modelClassCache[$cacheKey])) {
            $this->setRequestAttributes($request, $this->modelClassCache[$cacheKey]);

            return $next($request);
        }

        $pathSegments = array_slice($segments, 2);
        $result = $this->resolveModelClass($pathSegments);

        $this->modelClassCache[$cacheKey] = $result;
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        $this->setRequestAttributes($request, $result);

        return $next($request);
    }

    private function isExceptionRoute(string $path): bool
    {
        foreach (self::EXCEPTION_ROUTES as $exceptionRoute) {
            if (str_starts_with($path, $exceptionRoute)) {
                return true;
            }
        }

        return false;
    }

    private function resolveModelClass(array $pathSegments): array
    {
        if ($this->isPivotRoute($pathSegments)) {
            return $this->processPivotRoute($pathSegments);
        }

        return $this->processMainRoute($pathSegments);
    }

    private function isPivotRoute(array $pathSegments): bool
    {
        if (count($pathSegments) < 3) {
            return false;
        }

        foreach ($pathSegments as $i => $segment) {
            if (
                is_numeric($segment)
                && isset($pathSegments[$i + 1])
                && ! is_numeric($pathSegments[$i + 1])
                && preg_match('/^[a-zA-Z_-]+$/', $pathSegments[$i + 1])
            ) {
                return true;
            }
        }

        return false;
    }

    private function processPivotRoute(array $pathSegments): array
    {
        $parentIdIndex = null;
        $parentId = null;
        $originalRelation = null;

        for ($i = count($pathSegments) - 1; $i >= 0; $i--) {
            $segment = $pathSegments[$i];

            if (
                ! is_numeric($segment)
                && preg_match('/^[a-zA-Z_-]+$/', $segment)
                && $i > 0
                && is_numeric($pathSegments[$i - 1])
            ) {
                $originalRelation = $segment;
                $parentId = (int) $pathSegments[$i - 1];
                $parentIdIndex = $i - 1;
                break;
            }
        }

        if ($parentIdIndex === null) {
            throw new InvalidArgumentException('Invalid pivot route structure');
        }

        $parentModelEndIndex = $parentIdIndex;

        for ($i = $parentIdIndex - 1; $i >= 0; $i--) {
            if (is_numeric($pathSegments[$i])) {
                $parentModelEndIndex = $i;
                break;
            }
        }

        $parentModelPath = [];
        for ($i = 0; $i < $parentModelEndIndex; $i++) {
            if (! is_numeric($pathSegments[$i])) {
                $parentModelPath[] = $pathSegments[$i];
            }
        }

        if ($parentModelEndIndex < $parentIdIndex && count($parentModelPath) > 0) {
            $baseModelClass = $this->buildModelClass($parentModelPath);
            $baseModel = new $baseModelClass;
            $intermediateRelation = null;
            for ($i = $parentModelEndIndex + 1; $i < $parentIdIndex; $i++) {
                if (! is_numeric($pathSegments[$i])) {
                    $intermediateRelation = Str::snake($pathSegments[$i]);
                    break;
                }
            }

            if ($intermediateRelation && method_exists($baseModel, $intermediateRelation)) {
                $intermediateRelationObj = $baseModel->{$intermediateRelation}();
                $mainModelClass = get_class($intermediateRelationObj->getRelated());
            } else {
                throw new InvalidArgumentException('Cannot resolve intermediate relation');
            }
        } else {
            $mainModelClass = $this->buildModelClass($parentModelPath);
        }

        $relationMethod = Str::snake($originalRelation);
        $parentModel = new $mainModelClass;

        if (! method_exists($parentModel, $relationMethod)) {
            throw new InvalidArgumentException("Relation '{$relationMethod}' not defined on {$mainModelClass}");
        }

        $relationObj = $parentModel->{$relationMethod}();
        $relatedInstance = $relationObj->getRelated();
        $pivotModelClass = get_class($relatedInstance);
        $relationId = null;

        if (isset($pathSegments[$parentIdIndex + 2]) && is_numeric($pathSegments[$parentIdIndex + 2])) {
            $relationId = (int) $pathSegments[$parentIdIndex + 2];
        }

        $tableName = end($parentModelPath);
        $fullPathWithIds = [];
        $skipNext = false;

        for ($i = 0; $i < count($pathSegments); $i++) {
            if ($skipNext) {
                $skipNext = false;

                continue;
            }

            $segment = $pathSegments[$i];

            if (! is_numeric($segment)) {
                $fullPathWithIds[] = $segment;
            } elseif (isset($pathSegments[$i + 1]) && ! is_numeric($pathSegments[$i + 1]) && preg_match('/^[a-zA-Z_-]+$/', $pathSegments[$i + 1])) {
                $fullPathWithIds[] = $segment;
            }
        }

        return [
            'isPivotRoute' => true,
            'parentModelClass' => $mainModelClass,
            'pivotModelClass' => $pivotModelClass,
            'relationName' => $relationMethod,
            'originalRelationName' => $originalRelation,
            'parentId' => $parentId,
            'relationId' => $relationId,
            'mainModelPath' => implode('/', $parentModelPath),
            'fullPathWithIds' => implode('/', $fullPathWithIds),
            'tableName' => $tableName,
            'pivotTableName' => $relatedInstance->getTable(),
            'modelClass' => $pivotModelClass,
            'fullPath' => implode('/', $pathSegments),
        ];
    }

    private function processMainRoute(array $pathSegments): array
    {
        $modelPath = $pathSegments;
        if (is_numeric(end($pathSegments))) {
            $modelPath = array_slice($pathSegments, 0, -1);
        }

        if (empty($modelPath)) {
            throw new InvalidArgumentException('Empty model path');
        }

        $modelClass = $this->buildModelClass($modelPath);

        return [
            'isPivotRoute' => false,
            'modelClass' => $modelClass,
            'tableName' => end($modelPath),
            'mainModelPath' => implode('/', $modelPath),
            'fullPath' => implode('/', $pathSegments),
        ];
    }

    private function buildModelClass(array $pathSegments, ?string $customName = null): string
    {
        $nsParts = array_map([Str::class, 'studly'], $pathSegments);
        $namespace = 'App\\Models\\'.implode('\\', $nsParts);
        $className = $customName ?: (Str::studly(end($pathSegments)).'Model');

        return $namespace.'\\'.$className;
    }

    private function setRequestAttributes(Request $request, array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            $request->attributes->set($key, $value);
        }
    }
}
