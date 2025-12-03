<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ModelResolution\DTOs\ResolvedModel;
use App\Services\ModelResolution\ModelResolutionService;
use App\Services\ModelResolution\Support\ExceptionRouteChecker;
use Closure;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

final class ValidateModule
{
    private array $runtimeCache = [];

    public function __construct(
        private readonly ModelResolutionService $resolutionService,
        private readonly ExceptionRouteChecker $exceptionChecker,
        private readonly CacheRepository $cache,
    ) {
    }

    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->shouldProcess($request)) {
            return $next($request);
        }

        try {
            $resolvedModel = $this->resolveModel($request);
            $this->attachAttributesToRequest($request, $resolvedModel);
        } catch (InvalidArgumentException $e) {
            Log::warning('Model resolution failed', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);

            // İsterseniz exception fırlatabilir veya 404 dönebilirsiniz
            // abort(404, 'Resource not found');
        } catch (Throwable $e) {
            Log::error('Unexpected error in model resolution', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $next($request);
    }

    private function shouldProcess(Request $request): bool
    {
        $segments = $request->segments();

        if (count($segments) < config('model-resolution.minimum_segments', 3)) {
            return false;
        }

        $pathAfterApi = implode('/', array_slice($segments, 2));

        return !$this->exceptionChecker->isException($pathAfterApi);
    }

    private function resolveModel(Request $request): ResolvedModel
    {
        $cacheKey = $this->generateCacheKey($request);

        // Runtime cache kontrolü
        if (isset($this->runtimeCache[$cacheKey])) {
            return $this->runtimeCache[$cacheKey];
        }

        // Persistent cache kontrolü
        if (config('model-resolution.cache.enabled', true)) {
            $cached = $this->cache->get($cacheKey);

            if ($cached instanceof ResolvedModel) {
                $this->runtimeCache[$cacheKey] = $cached;

                return $cached;
            }
        }

        // Yeni resolution
        $pathSegments = array_slice($request->segments(), 2);
        $resolvedModel = $this->resolutionService->resolve($pathSegments);

        // Cache'e kaydet
        $this->cacheResolvedModel($cacheKey, $resolvedModel);

        return $resolvedModel;
    }

    private function generateCacheKey(Request $request): string
    {
        $prefix = config('model-resolution.cache.prefix', 'model_resolution');
        $segments = implode('/', $request->segments());

        return $prefix.'_'.md5($segments);
    }

    private function cacheResolvedModel(string $cacheKey, ResolvedModel $resolvedModel): void
    {
        $this->runtimeCache[$cacheKey] = $resolvedModel;

        if (config('model-resolution.cache.enabled', true)) {
            $ttl = config('model-resolution.cache.ttl', 3600);
            $this->cache->put($cacheKey, $resolvedModel, $ttl);
        }
    }

    private function attachAttributesToRequest(Request $request, ResolvedModel $resolvedModel): void
    {
        foreach ($resolvedModel->toArray() as $key => $value) {
            $request->attributes->set($key, $value);
        }
    }
}
