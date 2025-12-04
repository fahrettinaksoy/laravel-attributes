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
    private array $cache = [];

    private const CACHE_TTL = 3600;
    private const MIN_SEGMENTS = 3;
    private const SKIP_SEGMENTS = 2;

    private const EXCEPTION_ROUTES = [
        // Add your exception routes here
        // 'auth/login',
        // 'auth/register',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->shouldProcess($request)) {
            return $next($request);
        }

        $segments = array_slice($request->segments(), self::SKIP_SEGMENTS);
        $result = $this->resolveWithCache($request, $segments);

        foreach ($result as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $next($request);
    }

    private function shouldProcess(Request $request): bool
    {
        if (count($request->segments()) < self::MIN_SEGMENTS) {
            return false;
        }

        $path = implode('/', array_slice($request->segments(), self::SKIP_SEGMENTS));

        foreach (self::EXCEPTION_ROUTES as $exception) {
            if (str_starts_with($path, $exception)) {
                return false;
            }
        }

        return true;
    }

    private function resolveWithCache(Request $request, array $segments): array
    {
        $key = 'model_resolution_' . md5(implode('/', $request->segments()));

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if ($cached = Cache::get($key)) {
            return $this->cache[$key] = $cached;
        }

        $result = $this->resolve($segments);
        $this->cache[$key] = $result;
        Cache::put($key, $result, self::CACHE_TTL);

        return $result;
    }

    private function resolve(array $segments): array
    {
        return $this->hasPivotPattern($segments)
            ? $this->resolvePivot($segments)
            : $this->resolveMain($segments);
    }

    private function hasPivotPattern(array $segments): bool
    {
        if (count($segments) < 3) {
            return false;
        }

        foreach ($segments as $i => $segment) {
            $next = $segments[$i + 1] ?? null;

            if (is_numeric($segment) && $next && !is_numeric($next) && ctype_alpha($next)) {
                return true;
            }
        }

        return false;
    }

    private function resolveMain(array $segments): array
    {
        $modelPath = is_numeric(end($segments)) ? array_slice($segments, 0, -1) : $segments;

        if (empty($modelPath)) {
            throw new InvalidArgumentException('Empty model path');
        }

        return [
            'isPivotRoute' => false,
            'modelClass' => $this->buildModelClass($modelPath),
            'tableName' => end($modelPath),
            'mainModelPath' => implode('/', $modelPath),
            'fullPath' => implode('/', $segments),
        ];
    }

    private function resolvePivot(array $segments): array
    {
        $pivot = $this->extractPivotInfo($segments);
        $parentClass = $this->buildModelClass($pivot['parentPath']);

        if ($pivot['parentModelEndIndex'] < count($segments) - 2) {
            $parentModel = new $parentClass;
            $intermediateRelationName = null;
            for ($i = $pivot['parentModelEndIndex'] + 1; $i < count($segments); $i++) {
                if (!is_numeric($segments[$i]) && $i < count($segments) - 1 && is_numeric($segments[$i - 1])) {
                    $intermediateRelationName = Str::snake($segments[$i]);
                    break;
                }
            }

            if ($intermediateRelationName && method_exists($parentModel, $intermediateRelationName)) {
                $intermediateRelation = $parentModel->{$intermediateRelationName}();
                $parentClass = get_class($intermediateRelation->getRelated());
                $parentModel = new $parentClass;
            }
        } else {
            $parentModel = new $parentClass;
        }

        $relationMethod = Str::snake($pivot['relation']);

        if (!method_exists($parentModel, $relationMethod)) {
            throw new InvalidArgumentException(
                "Relation '{$relationMethod}' not found on {$parentClass}"
            );
        }

        $relationObj = $parentModel->{$relationMethod}();
        $relatedModel = $relationObj->getRelated();

        return [
            'isPivotRoute' => true,
            'modelClass' => get_class($relatedModel),
            'parentModelClass' => $parentClass,
            'pivotModelClass' => get_class($relatedModel),
            'relationName' => $relationMethod,
            'originalRelationName' => $pivot['relation'],
            'parentId' => $pivot['parentId'],
            'relationId' => $pivot['relationId'],
            'tableName' => end($pivot['parentPath']),
            'pivotTableName' => $relatedModel->getTable(),
            'mainModelPath' => implode('/', $pivot['parentPath']),
            'fullPath' => implode('/', $segments),
        ];
    }

    private function extractPivotInfo(array $segments): array
    {
        for ($i = count($segments) - 1; $i > 0; $i--) {
            $current = $segments[$i];
            $prev = $segments[$i - 1];

            if (!is_numeric($current) && ctype_alpha($current) && is_numeric($prev)) {
                $parentModelEndIndex = $i - 1;
                for ($j = $i - 2; $j >= 0; $j--) {
                    if (is_numeric($segments[$j])) {
                        $parentModelEndIndex = $j;
                        break;
                    }
                }

                $parentPath = [];
                for ($j = 0; $j < $parentModelEndIndex; $j++) {
                    if (!is_numeric($segments[$j])) {
                        $parentPath[] = $segments[$j];
                    }
                }

                return [
                    'parentId' => (int) $prev,
                    'relation' => $current,
                    'relationId' => isset($segments[$i + 1]) && is_numeric($segments[$i + 1]) ? (int) $segments[$i + 1] : null,
                    'parentPath' => $parentPath,
                    'parentModelEndIndex' => $parentModelEndIndex,
                ];
            }
        }

        throw new InvalidArgumentException('Invalid pivot route structure');
    }

    private function buildModelClass(array $segments): string
    {
        $parts = array_map([Str::class, 'studly'], $segments);
        $namespace = 'App\\Models\\' . implode('\\', $parts);
        $className = Str::studly(end($segments)) . 'Model';

        return $namespace . '\\' . $className;
    }
}
