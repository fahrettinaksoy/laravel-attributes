<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\Resolvers;

use App\Services\ModuleValidation\RoutePathParser;
use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;
use Illuminate\Support\Str;
use InvalidArgumentException;

class PivotRouteResolver implements RouteResolverInterface
{
    public function supports(array $segments): bool
    {
        return RoutePathParser::isPivotRoute($segments);
    }

    public function resolve(array $segments): ResolvedModuleDTO
    {
        $parsed = RoutePathParser::parsePivot($segments);

        $parentClass = $parsed->parentModelClass;
        $parentModel = new $parentClass;

        $relation = Str::snake($parsed->originalRelation);
        if (!method_exists($parentModel, $relation)) {
            throw new InvalidArgumentException("Relation '{$relation}' not found on {$parentClass}");
        }

        $relationObj = $parentModel->{$relation}();
        $related = $relationObj->getRelated();

        return new ResolvedModuleDTO(
            isPivotRoute: true,
            modelClass: get_class($related),
            tableName: $related->getTable(),
            parentModelClass: $parentClass,
            pivotModelClass: get_class($related),
            relationName: $relation,
            originalRelationName: $parsed->originalRelation,
            parentId: $parsed->parentId,
            relationId: $parsed->relationId,
            mainModelPath: $parsed->mainModelPath,
            fullPath: implode('/', $segments)
        );
    }
}
