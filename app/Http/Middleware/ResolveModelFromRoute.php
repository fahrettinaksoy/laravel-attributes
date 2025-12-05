<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Module\ModulePathResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ResolveModelFromRoute
{
    private array $cache = [];

    private const CACHE_TTL = 3600;

    private const MIN_SEGMENTS = 3;

    private const SKIP_SEGMENTS = 2;

    private const RELATION_PATTERN = '/^[a-zA-Z_-]+$/';

    private const EXCEPTION_ROUTES = [
        'auth/login',
        'auth/register',
        'auth/logout',
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        if (! $this->shouldResolve($request)) {
            return $next($request);
        }

        $segments = array_slice($request->segments(), self::SKIP_SEGMENTS);
        $result = $this->cached($segments, fn () => $this->resolve($segments));

        foreach ($result as $key => $value) {
            $request->attributes->set($key, $value);
        }

        return $next($request);
    }

    private function shouldResolve(Request $request): bool
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

    private function cached(array $segments, callable $callback): array
    {
        $key = 'model_res_'.md5(implode('/', $segments));

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if ($cached = Cache::get($key)) {
            return $this->cache[$key] = $cached;
        }

        $result = $callback();

        $this->cache[$key] = $result;
        Cache::put($key, $result, self::CACHE_TTL);

        return $result;
    }

    private function resolve(array $segments): array
    {
        return $this->hasPivotPattern($segments) ? $this->resolvePivot($segments) : $this->resolveMain($segments);
    }

    private function hasPivotPattern(array $segments): bool
    {
        if (count($segments) < 3) {
            return false;
        }

        for ($i = 0; $i < count($segments) - 1; $i++) {
            if ($this->isIdRelationPair($segments, $i)) {
                return true;
            }
        }

        return false;
    }

    private function resolveMain(array $segments): array
    {
        $path = is_numeric(end($segments))
            ? array_slice($segments, 0, -1)
            : $segments;

        if (empty($path)) {
            throw new InvalidArgumentException('Empty model path');
        }

        return [
            'isPivotRoute' => false,
            'modelClass' => ModulePathResolver::buildModelClass($path),
            'tableName' => end($path),
            'mainModelPath' => implode('/', $path),
            'fullPath' => implode('/', $segments),
        ];
    }

    private function resolvePivot(array $segments): array
    {
        $pivot = $this->findLastPivot($segments);
        $parentClass = $this->resolveChain($segments, $pivot);
        $parentModel = new $parentClass;
        $relationMethod = Str::snake($pivot['relation']);

        if (! method_exists($parentModel, $relationMethod)) {
            throw new InvalidArgumentException(
                "Relation '{$relationMethod}' not found on {$parentClass}"
            );
        }

        $related = $parentModel->{$relationMethod}()->getRelated();

        return [
            'isPivotRoute' => true,
            'modelClass' => get_class($related),
            'parentModelClass' => $parentClass,
            'pivotModelClass' => get_class($related),
            'relationName' => $relationMethod,
            'originalRelationName' => $pivot['relation'],
            'parentId' => $pivot['parentId'],
            'relationId' => $pivot['relationId'],
            'tableName' => end($pivot['basePath']),
            'pivotTableName' => $related->getTable(),
            'mainModelPath' => implode('/', $pivot['basePath']),
            'fullPath' => implode('/', $segments),
            'fullPathWithIds' => $this->pathWithIds($segments),
        ];
    }

    private function findLastPivot(array $segments): array
    {
        for ($i = count($segments) - 1; $i > 0; $i--) {
            if ($this->isIdRelationPair($segments, $i - 1)) {
                return [
                    'parentId' => (int) $segments[$i - 1],
                    'parentIdIndex' => $i - 1,
                    'relation' => $segments[$i],
                    'relationId' => isset($segments[$i + 1]) && is_numeric($segments[$i + 1]) ? (int) $segments[$i + 1] : null,
                    'basePath' => $this->extractBasePath($segments, $i - 1),
                ];
            }
        }

        throw new InvalidArgumentException('Invalid pivot structure');
    }

    private function resolveChain(array $segments, array $pivot): string
    {
        $basePath = $this->extractBasePath($segments, $pivot['parentIdIndex']);

        if (empty($basePath)) {
            throw new InvalidArgumentException('Cannot determine base path');
        }

        $modelClass = ModulePathResolver::buildModelClass($basePath);

        for ($i = 0; $i < $pivot['parentIdIndex'] - 1; $i++) {
            if ($this->isIdRelationPair($segments, $i)) {
                $relation = Str::snake($segments[$i + 1]);
                $model = new $modelClass;

                if (! method_exists($model, $relation)) {
                    throw new InvalidArgumentException(
                        "Relation '{$relation}' not found on {$modelClass}"
                    );
                }

                $modelClass = get_class($model->{$relation}()->getRelated());
                $i++;
            }
        }

        return $modelClass;
    }

    private function extractBasePath(array $segments, int $before): array
    {
        $path = [];

        for ($i = 0; $i < $before; $i++) {
            if (is_numeric($segments[$i])) {
                continue;
            }

            if ($i > 0 && is_numeric($segments[$i - 1])) {
                continue;
            }

            $path[] = $segments[$i];
        }

        return $path;
    }

    private function pathWithIds(array $segments): string
    {
        $result = [];

        for ($i = 0; $i < count($segments); $i++) {
            if (! is_numeric($segments[$i])) {
                $result[] = $segments[$i];
            } elseif ($this->isIdRelationPair($segments, $i)) {
                $result[] = $segments[$i];
            }
        }

        return implode('/', $result);
    }

    private function isIdRelationPair(array $segments, int $i): bool
    {
        return isset($segments[$i], $segments[$i + 1])
            && is_numeric($segments[$i])
            && ! is_numeric($segments[$i + 1])
            && preg_match(self::RELATION_PATTERN, $segments[$i + 1]);
    }
}
