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
use App\Services\Module\ModulePathResolver;
use App\Services\Module\ModuleRequestService;
use App\Services\PivotService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use RuntimeException;

class ModuleServiceProvider extends ServiceProvider
{
    private const EXCLUDED = ['CommonController'];

    public function register(): void
    {
        $this->app->singleton(ModuleRequestService::class, fn ($app) => new ModuleRequestService($app, $app->make(LoggerInterface::class))
        );

        $this->bindRepositories();
        $this->bindResources();
        $this->bindModules();
    }

    private function bindRepositories(): void
    {
        $this->app->when(BaseRepository::class)
            ->needs(Model::class)
            ->give(fn ($app) => $this->modelFromRequest($app));

        $this->app->when(BaseRepositoryCache::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepository::class);

        $this->app->when(BaseService::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepositoryCache::class);

        $this->app->singleton(PivotRepository::class);
        $this->app->when(PivotService::class)
            ->needs(PivotRepository::class)
            ->give(PivotRepository::class);
    }

    private function modelFromRequest(Application $app): Model
    {
        $request = $app->make(Request::class);
        $modelClass = $request->attributes->get('modelClass');

        if (! $modelClass) {
            throw new RuntimeException(
                'Model class not in request. Check ResolveModelFromRoute middleware.'
            );
        }

        return $app->make(ModelFactory::class)->create($modelClass);
    }

    private function bindResources(): void
    {
        $this->app->bind(BaseResource::class, fn ($app, $params) => new BaseResource($params['resource'] ?? new \stdClass)
        );

        $this->app->bind(BaseCollection::class, fn ($app, $params) => new BaseCollection($params['resource'] ?? new Collection)
        );
    }

    private function bindModules(): void
    {
        foreach ($this->controllers() as $controller) {
            $this->bindModule($controller);
        }
    }

    private function controllers(): array
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn (Route $r) => $this->parseController($r))
            ->filter()
            ->reject(fn ($c) => $this->isExcluded($c))
            ->unique()
            ->values()
            ->all();
    }

    private function parseController(Route $route): ?string
    {
        $action = $route->getAction();
        $key = $action['controller'] ?? $action['uses'] ?? null;

        return is_string($key) ? explode('@', $key)[0] : null;
    }

    private function isExcluded(string $controller): bool
    {
        foreach (self::EXCLUDED as $pattern) {
            if (str_contains($controller, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function bindModule(string $controller): void
    {
        $module = ModulePathResolver::extractModuleFromController($controller);
        $service = ModulePathResolver::buildServiceClass($module);
        $repo = ModulePathResolver::buildRepositoryClass($module);
        $interface = ModulePathResolver::buildRepositoryInterface($module);

        if (! $this->exists($service, $repo, $interface)) {
            return;
        }

        $this->app->when($service)
            ->needs(BaseRepositoryInterface::class)
            ->give(fn ($app) => $this->makeRepo($app, $repo, $interface, $module));
    }

    private function exists(string ...$classes): bool
    {
        foreach ($classes as $class) {
            if (! class_exists($class) && ! interface_exists($class)) {
                return false;
            }
        }

        return true;
    }

    private function makeRepo(Application $app, string $class, string $interface, string $module): object
    {
        $repo = $app->make($class);

        if (! $repo instanceof $interface) {
            throw new RuntimeException(
                "Module '{$module}': {$class} must implement {$interface}"
            );
        }

        return $repo;
    }
}
