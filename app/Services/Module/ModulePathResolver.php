<?php

declare(strict_types=1);

namespace App\Services\Module;

use Illuminate\Support\Str;

/**
 * Central path/namespace resolver for the entire module system.
 *
 * Eliminates code duplication across:
 * - Middleware (model resolution)
 * - Services (request resolution)
 * - Providers (DI bindings)
 *
 * Single source of truth for all namespace/path conversions.
 *
 * @package App\Services\Module
 */
class ModulePathResolver
{
    /**
     * Model namespace configuration.
     */
    private const MODEL_NAMESPACE = 'App\\Models\\';
    private const MODEL_SUFFIX = 'Model';

    /**
     * Request namespace configuration.
     */
    private const REQUEST_NAMESPACE = 'App\\Http\\Requests\\';
    private const REQUEST_SUFFIX = 'Request';
    private const PIVOT_SEGMENT = '\\Pivot\\';

    /**
     * Service namespace configuration.
     */
    private const SERVICE_NAMESPACE = 'App\\Services\\';
    private const SERVICE_SUFFIX = 'Service';

    /**
     * Repository namespace configuration.
     */
    private const REPOSITORY_NAMESPACE = 'App\\Repositories\\';
    private const REPOSITORY_SUFFIX = 'Repository';
    private const REPOSITORY_INTERFACE_SUFFIX = 'RepositoryInterface';

    // ==================== MODEL CLASS BUILDING ====================

    /**
     * Build fully qualified model class name from path segments.
     *
     * @param array $segments Path segments ['catalog', 'vehicles']
     * @return string 'App\Models\Catalog\Vehicles\VehiclesModel'
     *
     * @example
     * ModulePathResolver::buildModelClass(['catalog', 'vehicles'])
     * // Returns: 'App\Models\Catalog\Vehicles\VehiclesModel'
     */
    public static function buildModelClass(array $segments): string
    {
        if (empty($segments)) {
            throw new \InvalidArgumentException('Segments array cannot be empty');
        }

        $namespaceParts = array_map([Str::class, 'studly'], $segments);
        $namespace = self::MODEL_NAMESPACE . implode('\\', $namespaceParts);
        $className = Str::studly(end($segments)) . self::MODEL_SUFFIX;

        return $namespace . '\\' . $className;
    }

    /**
     * Build model class from path string.
     *
     * @param string $path 'catalog/vehicles'
     * @return string 'App\Models\Catalog\Vehicles\VehiclesModel'
     */
    public static function buildModelClassFromPath(string $path): string
    {
        $segments = self::pathToSegments($path);
        return self::buildModelClass($segments);
    }

    // ==================== REQUEST CLASS BUILDING ====================

    /**
     * Build FormRequest class name from path and action.
     *
     * @param string $path Model path 'catalog/vehicles'
     * @param string $action Action name 'store', 'update', etc.
     * @return string 'App\Http\Requests\Catalog\Vehicles\VehiclesStoreRequest'
     *
     * @example
     * ModulePathResolver::buildRequestClass('catalog/vehicles', 'store')
     * // Returns: 'App\Http\Requests\Catalog\Vehicles\VehiclesStoreRequest'
     */
    public static function buildRequestClass(string $path, string $action): string
    {
        $segments = self::pathToSegments($path);
        $namespaceParts = array_map([Str::class, 'studly'], $segments);

        $namespace = self::REQUEST_NAMESPACE . implode('\\', $namespaceParts);
        $className = Str::studly(end($segments)) . Str::studly($action) . self::REQUEST_SUFFIX;

        return $namespace . '\\' . $className;
    }

    /**
     * Build pivot FormRequest class name.
     *
     * @param string $parentPath Parent model path 'catalog/vehicles'
     * @param string $relation Relation name 'features'
     * @param string $action Action name 'store'
     * @return string 'App\Http\Requests\Catalog\Vehicles\Pivot\VehiclesFeatures\VehiclesFeaturesStoreRequest'
     *
     * @example
     * ModulePathResolver::buildPivotRequestClass('catalog/vehicles', 'features', 'store')
     * // Returns: 'App\Http\Requests\Catalog\Vehicles\Pivot\VehiclesFeatures\VehiclesFeaturesStoreRequest'
     */
    public static function buildPivotRequestClass(
        string $parentPath,
        string $relation,
        string $action
    ): string {
        $segments = self::pathToSegments($parentPath);
        $namespaceParts = array_map([Str::class, 'studly'], $segments);
        $parentTable = Str::studly(end($segments));
        $relationStudly = Str::studly($relation);

        // Build namespace: App\Http\Requests\Catalog\Vehicles\Pivot\VehiclesFeatures
        $namespace = self::REQUEST_NAMESPACE
            . implode('\\', $namespaceParts)
            . self::PIVOT_SEGMENT
            . $parentTable . $relationStudly;

        // Build class name: VehiclesFeaturesStoreRequest
        $className = $parentTable . $relationStudly . Str::studly($action) . self::REQUEST_SUFFIX;

        return $namespace . '\\' . $className;
    }

    // ==================== SERVICE CLASS BUILDING ====================

    /**
     * Build service class name from module name.
     *
     * @param string $module Module name 'Vehicles'
     * @return string 'App\Services\Vehicles\VehiclesService'
     *
     * @example
     * ModulePathResolver::buildServiceClass('Vehicles')
     * // Returns: 'App\Services\Vehicles\VehiclesService'
     */
    public static function buildServiceClass(string $module): string
    {
        return self::SERVICE_NAMESPACE . $module . '\\' . $module . self::SERVICE_SUFFIX;
    }

    // ==================== REPOSITORY CLASS BUILDING ====================

    /**
     * Build repository class name from module name.
     *
     * @param string $module Module name 'Vehicles'
     * @return string 'App\Repositories\Vehicles\VehiclesRepository'
     *
     * @example
     * ModulePathResolver::buildRepositoryClass('Vehicles')
     * // Returns: 'App\Repositories\Vehicles\VehiclesRepository'
     */
    public static function buildRepositoryClass(string $module): string
    {
        return self::REPOSITORY_NAMESPACE . $module . '\\' . $module . self::REPOSITORY_SUFFIX;
    }

    /**
     * Build repository interface name from module name.
     *
     * @param string $module Module name 'Vehicles'
     * @return string 'App\Repositories\Vehicles\VehiclesRepositoryInterface'
     *
     * @example
     * ModulePathResolver::buildRepositoryInterface('Vehicles')
     * // Returns: 'App\Repositories\Vehicles\VehiclesRepositoryInterface'
     */
    public static function buildRepositoryInterface(string $module): string
    {
        return self::REPOSITORY_NAMESPACE . $module . '\\' . $module . self::REPOSITORY_INTERFACE_SUFFIX;
    }

    // ==================== EXTRACTION HELPERS ====================

    /**
     * Extract module name from controller class.
     *
     * @param string $controllerClass 'App\Http\Controllers\Catalog\VehiclesController'
     * @return string 'Vehicles'
     *
     * @example
     * ModulePathResolver::extractModuleFromController('App\Http\Controllers\Catalog\VehiclesController')
     * // Returns: 'Vehicles'
     */
    public static function extractModuleFromController(string $controllerClass): string
    {
        return Str::before(class_basename($controllerClass), 'Controller');
    }

    /**
     * Extract model path from model class name.
     *
     * @param string $modelClass 'App\Models\Catalog\Vehicles\VehiclesModel'
     * @return string 'catalog/vehicles'
     *
     * @example
     * ModulePathResolver::extractPathFromModelClass('App\Models\Catalog\Vehicles\VehiclesModel')
     * // Returns: 'catalog/vehicles'
     */
    public static function extractPathFromModelClass(string $modelClass): string
    {
        $parts = explode('\\', $modelClass);

        // Remove: App, Models, and the class name (VehiclesModel)
        array_shift($parts); // Remove 'App'
        array_shift($parts); // Remove 'Models'
        array_pop($parts);   // Remove class name

        if (empty($parts)) {
            throw new \InvalidArgumentException("Invalid model class: {$modelClass}");
        }

        return strtolower(implode('/', $parts));
    }

    /**
     * Extract model name from model class (without Model suffix).
     *
     * @param string $modelClass 'App\Models\Catalog\Vehicles\VehiclesModel'
     * @return string 'Vehicles'
     */
    public static function extractModelName(string $modelClass): string
    {
        $className = class_basename($modelClass);
        return str_replace(self::MODEL_SUFFIX, '', $className);
    }

    // ==================== PATH MANIPULATION ====================

    /**
     * Convert path string to studly-cased namespace parts.
     *
     * @param string $path 'catalog/vehicles'
     * @return array ['Catalog', 'Vehicles']
     *
     * @example
     * ModulePathResolver::pathToNamespace('catalog/vehicles')
     * // Returns: ['Catalog', 'Vehicles']
     */
    public static function pathToNamespace(string $path): array
    {
        return array_map(
            [Str::class, 'studly'],
            self::pathToSegments($path)
        );
    }

    /**
     * Convert path string to segments array.
     *
     * @param string $path 'catalog/vehicles'
     * @return array ['catalog', 'vehicles']
     */
    public static function pathToSegments(string $path): array
    {
        return array_filter(explode('/', $path), fn($s) => !empty($s));
    }

    /**
     * Convert segments array to path string.
     *
     * @param array $segments ['catalog', 'vehicles']
     * @return string 'catalog/vehicles'
     */
    public static function segmentsToPath(array $segments): string
    {
        return implode('/', $segments);
    }

    /**
     * Normalize path (remove empty segments, trim slashes).
     *
     * @param string $path '/catalog//vehicles/'
     * @return string 'catalog/vehicles'
     */
    public static function normalizePath(string $path): string
    {
        return implode('/', self::pathToSegments($path));
    }

    // ==================== VALIDATION ====================

    /**
     * Check if model class exists.
     *
     * @param array $segments Path segments
     * @return bool True if class exists
     */
    public static function modelClassExists(array $segments): bool
    {
        $class = self::buildModelClass($segments);
        return class_exists($class);
    }

    /**
     * Check if request class exists.
     *
     * @param string $path Model path
     * @param string $action Action name
     * @return bool True if class exists
     */
    public static function requestClassExists(string $path, string $action): bool
    {
        $class = self::buildRequestClass($path, $action);
        return class_exists($class);
    }

    /**
     * Check if pivot request class exists.
     *
     * @param string $parentPath Parent model path
     * @param string $relation Relation name
     * @param string $action Action name
     * @return bool True if class exists
     */
    public static function pivotRequestClassExists(
        string $parentPath,
        string $relation,
        string $action
    ): bool {
        $class = self::buildPivotRequestClass($parentPath, $relation, $action);
        return class_exists($class);
    }

    // ==================== DEBUGGING ====================

    /**
     * Get all possible class names for a given path.
     * Useful for debugging/testing.
     *
     * @param string $path Model path
     * @return array All generated class names
     */
    public static function debugPath(string $path): array
    {
        $segments = self::pathToSegments($path);
        $module = Str::studly(end($segments));

        return [
            'path' => $path,
            'segments' => $segments,
            'namespace' => self::pathToNamespace($path),
            'model' => self::buildModelClass($segments),
            'service' => self::buildServiceClass($module),
            'repository' => self::buildRepositoryClass($module),
            'repository_interface' => self::buildRepositoryInterface($module),
            'request_store' => self::buildRequestClass($path, 'store'),
            'request_update' => self::buildRequestClass($path, 'update'),
        ];
    }

    /**
     * Get resolver configuration.
     *
     * @return array Configuration values
     */
    public static function getConfig(): array
    {
        return [
            'model' => [
                'namespace' => self::MODEL_NAMESPACE,
                'suffix' => self::MODEL_SUFFIX,
            ],
            'request' => [
                'namespace' => self::REQUEST_NAMESPACE,
                'suffix' => self::REQUEST_SUFFIX,
                'pivot_segment' => self::PIVOT_SEGMENT,
            ],
            'service' => [
                'namespace' => self::SERVICE_NAMESPACE,
                'suffix' => self::SERVICE_SUFFIX,
            ],
            'repository' => [
                'namespace' => self::REPOSITORY_NAMESPACE,
                'suffix' => self::REPOSITORY_SUFFIX,
                'interface_suffix' => self::REPOSITORY_INTERFACE_SUFFIX,
            ],
        ];
    }
}
