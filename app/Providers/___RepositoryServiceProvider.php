<?php

declare(strict_types=1);

namespace App\Providers;

use App\Factories\ModelFactory;
use App\Http\Resources\BaseCollection;
use App\Http\Resources\BaseResource;
use App\Repositories\BaseRepository;
use App\Repositories\BaseRepositoryCache;
use App\Repositories\BaseRepositoryInterface;
use App\Repositories\PivotRepository;
use App\Services\BaseService;
use App\Services\PivotService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Handles dependency injection for repositories, services, and resources.
 */
class RepositoryServiceProvider extends ServiceProvider
{
    private const COMMON_CONTROLLERS = [
        'App\Http\Controllers\Common\CommonController',
    ];

    public function register(): void
    {
        $this->registerBase();
        $this->registerResources();
        $this->registerPivot();
        $this->registerModules();
    }

    // ==================== BASE BINDINGS ====================

    private function registerBase(): void
    {
        // BaseRepository needs Model
        $this->app->when(BaseRepository::class)
            ->needs(Model::class)
            ->give(fn () => $this->resolveModel());

        // BaseRepositoryCache -> BaseRepository
        $this->app->when(BaseRepositoryCache::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepository::class);

        // BaseService -> BaseRepositoryCache
        $this->app->when(BaseService::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepositoryCache::class);
    }

    private function resolveModel(): Model
    {
        $request = $this->app->make(Request::class);
        $factory = $this->app->make(ModelFactory::class);
        $modelClass = $request->attributes->get('modelClass');

        return $factory->create($modelClass);
    }

    // ==================== RESOURCE BINDINGS ====================

    private function registerResources(): void
    {
        $this->app->bind(BaseResource::class, function ($app, $params) {
            return new BaseResource($params['resource'] ?? new \stdClass);
        });

        $this->app->bind(BaseCollection::class, function ($app, $params) {
            return new BaseCollection($params['resource'] ?? new Collection);
        });
    }

    // ==================== PIVOT BINDINGS ====================

    private function registerPivot(): void
    {
        $this->app->singleton(PivotRepository::class);

        $this->app->when(PivotService::class)
            ->needs(PivotRepository::class)
            ->give(PivotRepository::class);
    }

    // ==================== MODULE BINDINGS ====================

    private function registerModules(): void
    {
        foreach ($this->getControllers() as $controller) {
            $this->bindModule($controller);
        }
    }

    private function getControllers(): array
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn (Route $route) => $this->extractController($route))
            ->filter()
            ->reject(fn ($c) => in_array($c, self::COMMON_CONTROLLERS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function extractController(Route $route): ?string
    {
        $action = $route->getAction();
        $controller = $action['controller'] ?? $action['uses'] ?? null;

        return is_string($controller) ? explode('@', $controller)[0] : null;
    }

    private function bindModule(string $controller): void
    {
        $module = Str::before(class_basename($controller), 'Controller');

        $service = "App\\Services\\{$module}\\{$module}Service";
        $repository = "App\\Repositories\\{$module}\\{$module}Repository";
        $interface = "App\\Repositories\\{$module}\\{$module}RepositoryInterface";

        // Skip if classes don't exist
        if (! class_exists($service) || ! class_exists($repository) || ! interface_exists($interface)) {
            return;
        }

        $this->app->when($service)
            ->needs(BaseRepositoryInterface::class)
            ->give(function () use ($repository, $interface) {
                $repo = $this->app->make($repository);

                if (! $repo instanceof $interface) {
                    throw new RuntimeException(
                        sprintf('%s must implement %s', get_class($repo), $interface)
                    );
                }

                return $repo;
            });
    }
}
