<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\ModuleValidation\ModelResolver;
use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class ValidateModule
{
    private const CACHE_TTL = 3600;

    private const EXCEPTION_ROUTES = [
        'definition/location/search',
        'catalog/availability/check',
    ];

    public function __construct(
        private readonly ModelResolver $resolver
    ) {}

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

        $cacheKey = 'module_resolve_' . md5($request->path());

        /** @var ResolvedModuleDTO $resolved */
        $resolved = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($segments) {
            return $this->resolver->resolve(array_slice($segments, 2));
        });

        $this->writeAttributesToRequest($request, $resolved);

        return $next($request);
    }

    private function isExceptionRoute(string $path): bool
    {
        foreach (self::EXCEPTION_ROUTES as $route) {
            if (str_starts_with($path, $route)) {
                return true;
            }
        }
        return false;
    }

    private function writeAttributesToRequest(Request $request, ResolvedModuleDTO $dto): void
    {
        foreach ($dto->toArray() as $k => $v) {
            $request->attributes->set($k, $v);
        }
    }
}
