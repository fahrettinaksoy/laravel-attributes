<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation;

use App\Services\ModuleValidation\Resolvers\MainRouteResolver;
use App\Services\ModuleValidation\Resolvers\PivotRouteResolver;
use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;
use InvalidArgumentException;

class ModelResolver
{
    public function __construct(
        private readonly PivotRouteResolver $pivotResolver,
        private readonly MainRouteResolver $mainResolver,
    ) {}

    public function resolve(array $segments): ResolvedModuleDTO
    {
        if ($this->pivotResolver->supports($segments)) {
            return $this->pivotResolver->resolve($segments);
        }

        return $this->mainResolver->resolve($segments);
    }
}
