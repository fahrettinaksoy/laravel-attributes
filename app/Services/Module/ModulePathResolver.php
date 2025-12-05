<?php

declare(strict_types=1);

namespace App\Services\Module;

use Illuminate\Support\Str;

class ModulePathResolver
{
    private const NS_MODEL = 'App\\Models\\';

    private const NS_REQUEST = 'App\\Http\\Requests\\';

    private const NS_SERVICE = 'App\\Services\\';

    private const NS_REPO = 'App\\Repositories\\';

    private const SUFFIX_MODEL = 'Model';

    private const SUFFIX_REQUEST = 'Request';

    private const SUFFIX_SERVICE = 'Service';

    private const SUFFIX_REPO = 'Repository';

    private const SUFFIX_INTERFACE = 'RepositoryInterface';

    private const PIVOT = '\\Pivot\\';

    public static function buildModelClass(array $segments): string
    {
        if (empty($segments)) {
            throw new \InvalidArgumentException('Segments cannot be empty');
        }

        return self::buildClass(
            self::NS_MODEL,
            $segments,
            Str::studly(end($segments)).self::SUFFIX_MODEL
        );
    }

    public static function extractPathFromModelClass(string $class): string
    {
        $parts = explode('\\', $class);

        array_shift($parts);
        array_shift($parts);
        array_pop($parts);

        if (empty($parts)) {
            throw new \InvalidArgumentException("Invalid model class: {$class}");
        }

        return strtolower(implode('/', $parts));
    }

    public static function buildRequestClass(string $path, string $action): string
    {
        $segments = self::toSegments($path);
        $base = Str::studly(end($segments));

        return self::buildClass(self::NS_REQUEST, $segments, $base.Str::studly($action).self::SUFFIX_REQUEST);
    }

    public static function buildPivotRequestClass(string $parentPath, string $relation, string $action): string
    {
        $segments = self::toSegments($parentPath);
        $parent = Str::studly(end($segments));
        $rel = Str::studly($relation);
        $namespace = self::NS_REQUEST.self::toNamespace($segments).self::PIVOT.$parent.$rel;
        $className = $parent.$rel.Str::studly($action).self::SUFFIX_REQUEST;

        return $namespace.'\\'.$className;
    }

    public static function buildServiceClass(string $module): string
    {
        return self::buildModuleClass(self::NS_SERVICE, $module, self::SUFFIX_SERVICE);
    }

    public static function buildRepositoryClass(string $module): string
    {
        return self::buildModuleClass(self::NS_REPO, $module, self::SUFFIX_REPO);
    }

    public static function buildRepositoryInterface(string $module): string
    {
        return self::buildModuleClass(self::NS_REPO, $module, self::SUFFIX_INTERFACE);
    }

    public static function extractModuleFromController(string $controller): string
    {
        return Str::before(class_basename($controller), 'Controller');
    }

    private static function buildClass(
        $baseNamespace, array $segments, string $className): string
    {
        return $baseNamespace.self::toNamespace($segments).'\\'.$className;
    }

    private static function buildModuleClass(string $baseNamespace, string $module, string $suffix): string
    {
        return $baseNamespace.$module.'\\'.$module.$suffix;
    }

    private static function toNamespace(array $segments): string
    {
        return implode('\\', array_map([Str::class, 'studly'], $segments));
    }

    private static function toSegments(string $path): array
    {
        return array_filter(explode('/', $path), fn ($s) => ! empty($s));
    }
}
