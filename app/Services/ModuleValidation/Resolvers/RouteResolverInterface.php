<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\Resolvers;

use App\Services\ModuleValidation\DTO\ResolvedModuleDTO;

interface RouteResolverInterface
{
    public function supports(array $segments): bool;

    public function resolve(array $segments): ResolvedModuleDTO;
}
