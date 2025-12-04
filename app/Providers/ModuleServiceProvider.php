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

/**
 * Unified service provider for module system.
 *
 * Registers:
 * - Module services (ModuleRequestService)
 * - Repository layer (Base, Cache, Pivot)
 * - Resource layer (BaseResource, BaseCollection)
 * - Dynamic module bindings (per controller)
 *
 * Replaces: RepositoryServiceProvider
 *
 * @package App\Providers
 */
class ModuleServiceProvider extends ServiceProvider
{
    /**
     * Controllers to exclude from dynamic binding.
     *
     * @var array<string>
     */
    private const EXCLUDED_CONTROLLERS = [
        'App\Http\Controllers\Common\CommonController',
    ];

    /**
     * Register services.
     *
     * This method is called first during application bootstrap.
     * Register all service bindings here.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerCoreServices();
        $this->registerRepositoryLayer();
        $this->registerResourceLayer();
        $this->registerDynamicBindings();
    }

    /**
     * Bootstrap services.
     *
     * This method is called after all providers are registered.
     * Perform any post-registration actions here.
     *
     * @return void
     */
    public function boot(): void
    {
        // Future: Register commands, migrations, etc.
    }

    // ==================== CORE SERVICES ====================

    /**
     * Register core module services.
     *
     * @return void
     */
    private function registerCoreServices(): void
    {
        // ModuleRequestService - singleton for request resolution
        $this->app->singleton(ModuleRequestService::class, function (Application $app) {
            return new ModuleRequestService(
                container: $app,
                logger: $app->make(LoggerInterface::class),
            );
        });
    }

    // ==================== REPOSITORY LAYER ====================

    /**
     * Register repository layer bindings.
     *
     * Hierarchy:
     * BaseService -> BaseRepositoryCache -> BaseRepository -> Model
     *
     * @return void
     */
    private function registerRepositoryLayer(): void
    {
        $this->registerBaseRepository();
        $this->registerBaseRepositoryCache();
        $this->registerBaseService();
        $this->registerPivotBindings();
    }

    /**
     * Register BaseRepository with dynamic model resolution.
     *
     * The model is resolved from request attributes set by
     * ResolveModelFromRoute middleware.
     *
     * @return void
     */
    private function registerBaseRepository(): void
    {
        $this->app->when(BaseRepository::class)
            ->needs(Model::class)
            ->give(function (Application $app): Model {
                return $this->resolveModelFromRequest($app);
            });
    }

    /**
     * Register BaseRepositoryCache with BaseRepository dependency.
     *
     * Provides caching layer over base repository operations.
     *
     * @return void
     */
    private function registerBaseRepositoryCache(): void
    {
        $this->app->when(BaseRepositoryCache::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepository::class);
    }

    /**
     * Register BaseService with BaseRepositoryCache dependency.
     *
     * Services consume repositories through the cache layer.
     *
     * @return void
     */
    private function registerBaseService(): void
    {
        $this->app->when(BaseService::class)
            ->needs(BaseRepositoryInterface::class)
            ->give(BaseRepositoryCache::class);
    }

    /**
     * Register pivot-specific bindings.
     *
     * PivotRepository and PivotService for relationship operations.
     *
     * @return void
     */
    private function registerPivotBindings(): void
    {
        // PivotRepository - singleton
        $this->app->singleton(PivotRepository::class);

        // PivotService - uses PivotRepository
        $this->app->when(PivotService::class)
            ->needs(PivotRepository::class)
            ->give(function (Application $app) {
                return $app->make(PivotRepository::class);
            });
    }

    /**
     * Resolve model instance from request attributes.
     *
     * Uses ModelFactory to create model from class name
     * set by ResolveModelFromRoute middleware.
     *
     * @param Application $app Application instance
     * @return Model Resolved model instance
     * @throws RuntimeException If model class not found in request
     */
    private function resolveModelFromRequest(Application $app): Model
    {
        $request = $app->make(Request::class);
        $modelClass = $request->attributes->get('modelClass');

        if (!$modelClass) {
            throw new RuntimeException(
                'Model class not found in request attributes. ' .
                'Ensure ResolveModelFromRoute middleware is registered.'
            );
        }

        $factory = $app->make(ModelFactory::class);

        return $factory->create($modelClass);
    }

    // ==================== RESOURCE LAYER ====================

    /**
     * Register API resource layer bindings.
     *
     * Provides consistent JSON transformation layer.
     *
     * @return void
     */
    private function registerResourceLayer(): void
    {
        $this->registerBaseResource();
        $this->registerBaseCollection();
    }

    /**
     * Register BaseResource for single model transformation.
     *
     * @return void
     */
    private function registerBaseResource(): void
    {
        $this->app->bind(BaseResource::class, function (Application $app, array $parameters) {
            $resource = $parameters['resource'] ?? new \stdClass;

            return new BaseResource($resource);
        });
    }

    /**
     * Register BaseCollection for model collection transformation.
     *
     * @return void
     */
    private function registerBaseCollection(): void
    {
        $this->app->bind(BaseCollection::class, function (Application $app, array $parameters) {
            $resource = $parameters['resource'] ?? new Collection;

            return new BaseCollection($resource);
        });
    }

    // ==================== DYNAMIC MODULE BINDINGS ====================

    /**
     * Register dynamic module-specific bindings.
     *
     * Scans all registered routes and creates bindings for each
     * module's Service -> Repository -> Interface chain.
     *
     * Example:
     * VehiclesService -> VehiclesRepository (implements VehiclesRepositoryInterface)
     *
     * @return void
     */
    private function registerDynamicBindings(): void
    {
        $controllers = $this->discoverControllers();

        foreach ($controllers as $controllerClass) {
            $this->bindModuleServices($controllerClass);
        }
    }

    /**
     * Discover all controllers from registered routes.
     *
     * Filters out:
     * - Closure routes
     * - Common/shared controllers
     * - Duplicate controllers
     *
     * @return array<string> Unique controller class names
     */
    private function discoverControllers(): array
    {
        return collect($this->app['router']->getRoutes()->getRoutes())
            ->map(fn(Route $route) => $this->extractControllerClass($route))
            ->filter()
            ->reject(fn(string $controller) => $this->isExcludedController($controller))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Extract controller class from route.
     *
     * @param Route $route Route instance
     * @return string|null Controller class name or null
     */
    private function extractControllerClass(Route $route): ?string
    {
        $action = $route->getAction();

        // Try 'controller' key first (Laravel 11+)
        if (isset($action['controller']) && is_string($action['controller'])) {
            return explode('@', $action['controller'])[0];
        }

        // Fallback to 'uses' key (Laravel 10-)
        if (isset($action['uses']) && is_string($action['uses'])) {
            return explode('@', $action['uses'])[0];
        }

        return null;
    }

    /**
     * Check if controller should be excluded from dynamic binding.
     *
     * @param string $controllerClass Controller class name
     * @return bool True if should be excluded
     */
    private function isExcludedController(string $controllerClass): bool
    {
        foreach (self::EXCLUDED_CONTROLLERS as $excluded) {
            if (str_contains($controllerClass, $excluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Bind module-specific services.
     *
     * Creates contextual binding:
     * When ModuleService needs BaseRepositoryInterface,
     * give it ModuleRepository (validated against ModuleRepositoryInterface).
     *
     * @param string $controllerClass Controller class name
     * @return void
     */
    private function bindModuleServices(string $controllerClass): void
    {
        // Extract module name from controller
        $module = ModulePathResolver::extractModuleFromController($controllerClass);

        // Build class names using ModulePathResolver
        $serviceClass = ModulePathResolver::buildServiceClass($module);
        $repositoryClass = ModulePathResolver::buildRepositoryClass($module);
        $repositoryInterface = ModulePathResolver::buildRepositoryInterface($module);

        // Validate classes exist
        if (!$this->validateModuleClasses($serviceClass, $repositoryClass, $repositoryInterface)) {
            return;
        }

        // Create contextual binding
        $this->app->when($serviceClass)
            ->needs(BaseRepositoryInterface::class)
            ->give(function (Application $app) use ($repositoryClass, $repositoryInterface, $module) {
                return $this->resolveModuleRepository(
                    $app,
                    $repositoryClass,
                    $repositoryInterface,
                    $module
                );
            });
    }

    /**
     * Validate that module classes exist.
     *
     * @param string $serviceClass Service class name
     * @param string $repositoryClass Repository class name
     * @param string $repositoryInterface Repository interface name
     * @return bool True if all classes exist
     */
    private function validateModuleClasses(
        string $serviceClass,
        string $repositoryClass,
        string $repositoryInterface
    ): bool {
        return class_exists($serviceClass)
            && class_exists($repositoryClass)
            && interface_exists($repositoryInterface);
    }

    /**
     * Resolve and validate module repository.
     *
     * Creates repository instance and validates it implements
     * the required interface.
     *
     * @param Application $app Application instance
     * @param string $repositoryClass Repository class name
     * @param string $repositoryInterface Required interface name
     * @param string $module Module name (for error messages)
     * @return object Repository instance
     * @throws RuntimeException If repository doesn't implement interface
     */
    private function resolveModuleRepository(
        Application $app,
        string $repositoryClass,
        string $repositoryInterface,
        string $module
    ): object {
        $repository = $app->make($repositoryClass);

        // Validate interface implementation
        if (!$repository instanceof $repositoryInterface) {
            throw new RuntimeException(sprintf(
                'Module "%s": %s must implement %s',
                $module,
                $repositoryClass,
                $repositoryInterface
            ));
        }

        return $repository;
    }

    // ==================== PROVIDER METADATA ====================

    /**
     * Get the services provided by this provider.
     *
     * For deferred providers, return array of provided services.
     * Not used currently as we want eager loading.
     *
     * @return array<string>
     */
    public function provides(): array
    {
        return [
            ModuleRequestService::class,
            BaseRepository::class,
            BaseRepositoryCache::class,
            BaseService::class,
            PivotRepository::class,
            PivotService::class,
            BaseResource::class,
            BaseCollection::class,
        ];
    }

    // ==================== DEBUGGING ====================

    /**
     * Get registered module bindings for debugging.
     *
     * @return array<string, array> Module name => binding info
     */
    public function getRegisteredModules(): array
    {
        $controllers = $this->discoverControllers();
        $modules = [];

        foreach ($controllers as $controller) {
            $module = ModulePathResolver::extractModuleFromController($controller);

            $modules[$module] = [
                'controller' => $controller,
                'service' => ModulePathResolver::buildServiceClass($module),
                'repository' => ModulePathResolver::buildRepositoryClass($module),
                'interface' => ModulePathResolver::buildRepositoryInterface($module),
                'exists' => [
                    'service' => class_exists(ModulePathResolver::buildServiceClass($module)),
                    'repository' => class_exists(ModulePathResolver::buildRepositoryClass($module)),
                    'interface' => interface_exists(ModulePathResolver::buildRepositoryInterface($module)),
                ],
            ];
        }

        return $modules;
    }

    /**
     * Get provider statistics.
     *
     * @return array Statistics data
     */
    public function getStats(): array
    {
        $controllers = $this->discoverControllers();
        $modules = $this->getRegisteredModules();

        $validModules = collect($modules)
            ->filter(fn($m) => $m['exists']['service']
                && $m['exists']['repository']
                && $m['exists']['interface'])
            ->count();

        return [
            'total_controllers' => count($controllers),
            'total_modules' => count($modules),
            'valid_modules' => $validModules,
            'invalid_modules' => count($modules) - $validModules,
            'excluded_controllers' => count(self::EXCLUDED_CONTROLLERS),
        ];
    }
}
