<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\ModelResolution\ModelResolutionService;
use App\Services\ModelResolution\Resolvers\MainRouteResolver;
use App\Services\ModelResolution\Resolvers\PivotRouteResolver;
use App\Services\ModelResolution\Support\ExceptionRouteChecker;
use App\Services\ModelResolution\Support\ModelClassBuilder;
use App\Services\ModelResolution\Support\PivotRouteParser;
use App\Services\ModelResolution\Support\RelationResolver;
use Illuminate\Support\ServiceProvider;

final class ModelResolutionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Support classes
        $this->registerSupportClasses();

        // Resolvers
        $this->registerResolvers();

        // Main service
        $this->registerModelResolutionService();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/model-resolution.php' => config_path('model-resolution.php'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../../config/model-resolution.php' => config_path('model-resolution.php'),
        ], 'model-resolution-config');
    }

    /**
     * Register support classes.
     */
    private function registerSupportClasses(): void
    {
        $this->app->singleton(ModelClassBuilder::class, function () {
            return new ModelClassBuilder(
                baseNamespace: config('model-resolution.base_namespace', 'App\\Models'),
                modelSuffix: config('model-resolution.model_suffix', 'Model'),
            );
        });

        $this->app->singleton(PivotRouteParser::class);

        $this->app->singleton(RelationResolver::class);

        $this->app->singleton(ExceptionRouteChecker::class, function () {
            return new ExceptionRouteChecker(
                exceptionRoutes: config('model-resolution.exception_routes', [
                    'definition/location/search',
                    'catalog/availability/check',
                ])
            );
        });
    }

    /**
     * Register model resolvers.
     */
    private function registerResolvers(): void
    {
        $this->app->singleton('model.resolver.main', function ($app) {
            return new MainRouteResolver(
                classBuilder: $app->make(ModelClassBuilder::class)
            );
        });

        $this->app->singleton('model.resolver.pivot', function ($app) {
            return new PivotRouteResolver(
                classBuilder: $app->make(ModelClassBuilder::class),
                parser: $app->make(PivotRouteParser::class),
                relationResolver: $app->make(RelationResolver::class),
            );
        });
    }

    /**
     * Register the main model resolution service.
     */
    private function registerModelResolutionService(): void
    {
        $this->app->singleton(ModelResolutionService::class, function ($app) {
            return new ModelResolutionService(
                resolvers: [
                    $app->make('model.resolver.pivot'), // Pivot önce kontrol edilmeli
                    $app->make('model.resolver.main'),  // Main sonra (catch-all)
                ]
            );
        });
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [
            ModelResolutionService::class,
            ModelClassBuilder::class,
            PivotRouteParser::class,
            RelationResolver::class,
            ExceptionRouteChecker::class,
            'model.resolver.main',
            'model.resolver.pivot',
        ];
    }
}
