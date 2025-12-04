<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Module\ModulePathResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Dynamically resolves API routes to Eloquent model classes.
 *
 * Extracts model information from URL segments and attaches metadata
 * to the request for downstream usage (controllers, repositories, services).
 *
 * Examples:
 * - GET /api/v1/catalog/vehicles/123
 *   → Resolves to VehiclesModel, sets request attributes
 *
 * - GET /api/v1/catalog/vehicles/123/features
 *   → Detects pivot route, resolves parent->relation
 *
 * - GET /api/v1/catalog/vehicles/123/features/456
 *   → Pivot route with specific relation item ID
 */
class ResolveModelFromRoute
{
    /**
     * Runtime cache to avoid repeated resolution within same request.
     */
    private array $runtimeCache = [];

    /**
     * Persistent cache TTL in seconds.
     */
    private const CACHE_TTL = 3600;

    /**
     * Minimum URL segments required (e.g., api/v1/module).
     */
    private const MIN_SEGMENTS = 3;

    /**
     * Segments to skip from URL (typically 'api' and 'v1').
     */
    private const SKIP_SEGMENTS = 2;

    /**
     * Routes that should bypass resolution.
     */
    private const EXCEPTION_ROUTES = [
        'auth/login',
        'auth/register',
        'auth/logout',
        'definition/location/search',
        'catalog/availability/check',
    ];

    /**
     * Handle incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip resolution if conditions not met
        if (!$this->shouldResolve($request)) {
            return $next($request);
        }

        // Extract path segments after API prefix
        $segments = array_slice($request->segments(), self::SKIP_SEGMENTS);

        // Resolve with caching
        $result = $this->resolveWithCache($segments);

        // Attach resolved data to request attributes
        $this->attachToRequest($request, $result);

        return $next($request);
    }

    /**
     * Determine if request should be resolved.
     */
    private function shouldResolve(Request $request): bool
    {
        // Must have minimum segments
        if (count($request->segments()) < self::MIN_SEGMENTS) {
            return false;
        }

        // Check if route is in exception list
        $path = implode('/', array_slice($request->segments(), self::SKIP_SEGMENTS));

        foreach (self::EXCEPTION_ROUTES as $exception) {
            if (str_starts_with($path, $exception)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Resolve with multi-layer caching.
     */
    private function resolveWithCache(array $segments): array
    {
        $cacheKey = $this->generateCacheKey($segments);

        // Check runtime cache first
        if (isset($this->runtimeCache[$cacheKey])) {
            return $this->runtimeCache[$cacheKey];
        }

        // Check persistent cache
        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $this->runtimeCache[$cacheKey] = $cached;
        }

        // Fresh resolution
        $result = $this->resolve($segments);

        // Cache the result
        $this->runtimeCache[$cacheKey] = $result;
        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Main resolution logic - determines route type and resolves accordingly.
     */
    private function resolve(array $segments): array
    {
        return $this->isPivotRoute($segments)
            ? $this->resolvePivotRoute($segments)
            : $this->resolveMainRoute($segments);
    }

    /**
     * Detect if route follows pivot pattern (ID followed by relation name).
     */
    private function isPivotRoute(array $segments): bool
    {
        if (count($segments) < 3) {
            return false;
        }

        // Look for pattern: numeric ID followed by alphabetic relation name
        foreach ($segments as $i => $segment) {
            if (is_numeric($segment)
                && isset($segments[$i + 1])
                && !is_numeric($segments[$i + 1])
                && preg_match('/^[a-zA-Z_-]+$/', $segments[$i + 1])
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve standard CRUD route.
     *
     * Pattern: /module/submodule[/id]
     * Example: /catalog/vehicles/123
     */
    private function resolveMainRoute(array $segments): array
    {
        // Remove trailing numeric ID if present
        $modelPath = is_numeric(end($segments))
            ? array_slice($segments, 0, -1)
            : $segments;

        if (empty($modelPath)) {
            throw new InvalidArgumentException('Empty model path');
        }

        // Build model class using helper
        $modelClass = ModulePathResolver::buildModelClass($modelPath);

        return [
            'isPivotRoute' => false,
            'modelClass' => $modelClass,
            'tableName' => end($modelPath),
            'mainModelPath' => implode('/', $modelPath),
            'fullPath' => implode('/', $segments),
        ];
    }

    /**
     * Resolve pivot/relationship route.
     *
     * Pattern: /parent-path/parent-id/relation-name[/relation-id]
     * Example: /catalog/vehicles/123/features/456
     */
    private function resolvePivotRoute(array $segments): array
    {
        // Parse pivot pattern to extract components
        $pivotInfo = $this->parsePivotPattern($segments);

        // Build parent model class
        $parentModelClass = ModulePathResolver::buildModelClass($pivotInfo['parentPath']);
        $parentModel = new $parentModelClass;

        // Get relation method (convert to snake_case)
        $relationMethod = Str::snake($pivotInfo['relation']);

        // Validate relation exists
        if (!method_exists($parentModel, $relationMethod)) {
            throw new InvalidArgumentException(
                "Relation '{$relationMethod}' not found on {$parentModelClass}"
            );
        }

        // Resolve relation to get related model
        $relationObj = $parentModel->{$relationMethod}();
        $relatedModel = $relationObj->getRelated();

        return [
            'isPivotRoute' => true,
            'modelClass' => get_class($relatedModel),
            'parentModelClass' => $parentModelClass,
            'pivotModelClass' => get_class($relatedModel),
            'relationName' => $relationMethod,
            'originalRelationName' => $pivotInfo['relation'],
            'parentId' => $pivotInfo['parentId'],
            'relationId' => $pivotInfo['relationId'],
            'tableName' => end($pivotInfo['parentPath']),
            'pivotTableName' => $relatedModel->getTable(),
            'mainModelPath' => implode('/', $pivotInfo['parentPath']),
            'fullPath' => implode('/', $segments),
            'fullPathWithIds' => $this->buildPathWithIds($segments),
        ];
    }

    /**
     * Parse pivot route pattern to extract components.
     *
     * Scans from end to find: ID -> relation pattern
     * Example: ['catalog', 'vehicles', '123', 'features', '456']
     * Returns: [parentId => 123, relation => 'features', relationId => 456, parentPath => ['catalog', 'vehicles']]
     */
    private function parsePivotPattern(array $segments): array
    {
        // Reverse scan to find last ID -> relation pattern
        for ($i = count($segments) - 1; $i > 0; $i--) {
            $current = $segments[$i];
            $previous = $segments[$i - 1];

            // Check for: non-numeric relation name preceded by numeric ID
            if (!is_numeric($current)
                && preg_match('/^[a-zA-Z_-]+$/', $current)
                && is_numeric($previous)
            ) {
                return [
                    'parentId' => (int) $previous,
                    'relation' => $current,
                    'relationId' => $this->extractRelationId($segments, $i),
                    'parentPath' => $this->extractParentPath($segments, $i - 1),
                ];
            }
        }

        throw new InvalidArgumentException('Invalid pivot route structure');
    }

    /**
     * Extract optional relation ID (segment after relation name).
     */
    private function extractRelationId(array $segments, int $relationIndex): ?int
    {
        $potentialId = $segments[$relationIndex + 1] ?? null;

        return is_numeric($potentialId) ? (int) $potentialId : null;
    }

    /**
     * Extract parent model path (non-numeric segments before parent ID).
     */
    private function extractParentPath(array $segments, int $beforeIndex): array
    {
        $path = [];

        for ($i = 0; $i < $beforeIndex; $i++) {
            if (!is_numeric($segments[$i])) {
                $path[] = $segments[$i];
            }
        }

        return $path;
    }

    /**
     * Build path including parent IDs for pivot routes.
     *
     * Example: ['catalog', 'vehicles', '123', 'features']
     *       -> 'catalog/vehicles/123/features'
     */
    private function buildPathWithIds(array $segments): string
    {
        $result = [];

        for ($i = 0; $i < count($segments); $i++) {
            $current = $segments[$i];
            $next = $segments[$i + 1] ?? null;

            // Include non-numeric segments
            if (!is_numeric($current)) {
                $result[] = $current;
                continue;
            }

            // Include numeric if followed by relation name
            if ($next !== null
                && !is_numeric($next)
                && preg_match('/^[a-zA-Z_-]+$/', $next)
            ) {
                $result[] = $current;
            }
        }

        return implode('/', $result);
    }

    /**
     * Generate cache key from segments.
     */
    private function generateCacheKey(array $segments): string
    {
        return 'model_resolution_' . md5(implode('/', $segments));
    }

    /**
     * Attach resolved data to request attributes.
     */
    private function attachToRequest(Request $request, array $data): void
    {
        foreach ($data as $key => $value) {
            $request->attributes->set($key, $value);
        }
    }

    /**
     * Clear all resolution caches (for testing/debugging).
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }
}
