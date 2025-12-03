<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Contracts;

use App\Services\ModelResolution\DTOs\ResolvedModel;

interface ModelResolverInterface
{
    public function canResolve(array $pathSegments): bool;

    public function resolve(array $pathSegments): ResolvedModel;
}
