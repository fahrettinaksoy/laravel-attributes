<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation;

use App\Services\ModuleValidation\DTO\ParsedPivotDTO;
use App\Services\ModuleValidation\DTO\ParsedMainDTO;
use Illuminate\Support\Str;
use InvalidArgumentException;

class RoutePathParser
{
    public static function isPivotRoute(array $segments): bool
    {
        foreach ($segments as $i => $seg) {
            if (is_numeric($seg) && isset($segments[$i+1]) && !is_numeric($segments[$i+1])) {
                return true;
            }
        }
        return false;
    }

    public static function parsePivot(array $segments): ParsedPivotDTO
    {
        $relation = null;
        $parentId = null;
        $index = null;

        foreach ($segments as $i => $seg) {
            if (!is_numeric($seg) && isset($segments[$i-1]) && is_numeric($segments[$i-1])) {
                $relation = $seg;
                $parentId = (int) $segments[$i-1];
                $index = $i;
                break;
            }
        }

        if (!$relation) {
            throw new InvalidArgumentException("Invalid pivot route structure");
        }

        $modelPath = [];
        for ($k = 0; $k < $index - 1; $k++) {
            if (!is_numeric($segments[$k])) {
                $modelPath[] = $segments[$k];
            }
        }

        $parentModelClass = self::buildClass($modelPath);

        $relationId = null;
        if (isset($segments[$index + 1]) && is_numeric($segments[$index + 1])) {
            $relationId = (int) $segments[$index + 1];
        }

        return new ParsedPivotDTO(
            parentModelClass: $parentModelClass,
            originalRelation: $relation,
            parentId: $parentId,
            relationId: $relationId,
            mainModelPath: implode('/', $modelPath)
        );
    }

    public static function parseMain(array $segments): ParsedMainDTO
    {
        $modelPath = $segments;

        if (is_numeric(end($modelPath))) {
            array_pop($modelPath);
        }

        $class = self::buildClass($modelPath);

        return new ParsedMainDTO(
            modelClass: $class,
            tableName: end($modelPath),
            mainModelPath: implode('/', $modelPath)
        );
    }

    public static function buildClass(array $segments): string
    {
        $ns = array_map(fn($s) => Str::studly($s), $segments);
        $className = Str::studly(end($segments)) . 'Model';
        return 'App\\Models\\' . implode('\\', $ns) . '\\' . $className;
    }
}
