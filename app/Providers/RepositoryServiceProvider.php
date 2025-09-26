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

class RepositoryServiceProvider extends ServiceProvider
{
    private const NAMESPACE_TEMPLATE = [
        'service' => 'App\Services\%s\%sService',
        'repository' => 'App\Repositories\%s\%sRepository',
        'interface' => 'App\Repositories\%s\%sRepositoryInterface',
    ];

    private const COMMON_CONTROLLERS = [
        'App\Http\Controllers\Common\CommonController',
    ];

    public function register(): void
    {
        $this->registerBaseBindings();
        $this->registerResourceBindings();
        $this->registerPivotBindings();
        $this->registerSpecialBindings();
    }

    private function registerBaseBindings(): void
    {
        $this->bindBaseRepository();
        $this->bindBaseRepositoryCache();
        $this->bindBaseService();
    }

    private function bindBaseRepository(): void
    {
        $this->app->when(BaseRepository::class)
            ->needs(Model::class)
            ->give(function (): ?Model {
                return $this->resolveModel();
            });
    }

    private function resolveModel(): Model
    {
        $request = $this->app->make(Request::class);
        $factory = $this->app->make(ModelFactory::class);
        $modelClass = $request->attributes->get('modelClass');

        return $factory->create($modelClass);
    }

    private function bindBaseRepositoryCache(): void
    {
        $this->app->when(BaseRepositoryCache::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepository::class);
    }

    private function bindBaseService(): void
    {
        $this->app->when(BaseService::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepositoryCache::class);
    }

    private function registerResourceBindings(): void
    {
        $this->bindBaseResource();
        $this->bindBaseCollection();
    }

    private function bindBaseResource(): void
    {
        $this->app->bind(BaseResource::class, function ($app, array $parameters = []) {
            $resource = $parameters['resource'] ?? new \stdClass;

            return new BaseResource($resource);
        });
    }

    private function bindBaseCollection(): void
    {
        $this->app->bind(BaseCollection::class, function ($app, array $parameters = []) {
            $resource = $parameters['resource'] ?? new Collection;

            return new BaseCollection($resource);
        });
    }

    private function registerPivotBindings(): void
    {
        $this->app->bind(PivotRepository::class, function ($app) {
            return new PivotRepository;
        });

        $this->app->when(PivotService::class)
            ->needs(PivotRepository::class)
            ->give(function ($app) {
                return $app->make(PivotRepository::class);
            });
    }

    private function registerSpecialBindings(): void
    {
        collect($this->findSpecialControllers())
            ->each(fn (string $controller) => $this->registerControllerBindings($controller));
    }

    private function findSpecialControllers(): array
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn (Route $route) => $this->getControllerFromRoute($route))
            ->filter()
            ->reject(fn (string $controller) => in_array($controller, self::COMMON_CONTROLLERS, true))
            ->unique()
            ->values()
            ->all();
    }

    private function getControllerFromRoute(Route $route): ?string
    {
        $action = $route->getAction();

        if (isset($action['controller']) && is_string($action['controller'])) {
            return explode('@', $action['controller'])[0];
        }

        if (isset($action['uses']) && is_string($action['uses'])) {
            return explode('@', $action['uses'])[0];
        }

        return null;
    }

    private function registerControllerBindings(string $controllerClass): void
    {
        $module = $this->getModuleNameFromController($controllerClass);

        $serviceClass = $this->getNamespacedClass('service', $module);
        $repositoryClass = $this->getNamespacedClass('repository', $module);
        $repositoryInterface = $this->getNamespacedClass('interface', $module);

        if (! $this->areClassesValid($serviceClass, $repositoryClass, $repositoryInterface)) {
            return;
        }

        $this->bindModuleRepository($serviceClass, $repositoryClass, $repositoryInterface);
    }

    private function getModuleNameFromController(string $controllerClass): string
    {
        return Str::before(class_basename($controllerClass), 'Controller');
    }

    private function getNamespacedClass(string $type, string $module): string
    {
        return sprintf(self::NAMESPACE_TEMPLATE[$type], $module, $module);
    }

    private function areClassesValid(string $serviceClass, string $repositoryClass, string $repositoryInterface): bool
    {
        return class_exists($serviceClass)
            && class_exists($repositoryClass)
            && interface_exists($repositoryInterface);
    }

    private function bindModuleRepository(
        string $serviceClass,
        string $repositoryClass,
        string $repositoryInterface,
    ): void {
        $this->app->when($serviceClass)
            ->needs(BaseRepositoryInterface::class)
            ->give(function () use ($repositoryClass, $repositoryInterface) {
                $repository = $this->app->make($repositoryClass);

                $this->validateRepository($repository, $repositoryInterface);

                return $repository;
            });
    }

    private function validateRepository(object $repository, string $repositoryInterface): void
    {
        if (! $repository instanceof $repositoryInterface) {
            throw new RuntimeException(sprintf(
                '%s must implement %s',
                get_class($repository),
                $repositoryInterface,
            ));
        }
    }
}
