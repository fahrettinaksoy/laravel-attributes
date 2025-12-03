<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\Resolvers;

use App\Services\ModuleValidation\RoutePathParser;
use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;

class MainRouteResolver implements RouteResolverInterface
{
    public function supports(array $segments): bool
    {
        return true;
    }

    public function resolve(array $segments): ResolvedModuleDTO
    {
        $parsed = RoutePathParser::parseMain($segments);

        return new ResolvedModuleDTO(
            isPivotRoute: false,
            modelClass: $parsed->modelClass,
            tableName: $parsed->tableName,
            parentModelClass: null,
            pivotModelClass: null,
            relationName: null,
            originalRelationName: null,
            parentId: null,
            relationId: null,
            mainModelPath: $parsed->mainModelPath,
            fullPath: implode('/', $segments)
        );
    }
}
