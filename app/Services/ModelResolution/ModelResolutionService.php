<?php

declare(strict_types=1);

namespace App\Services\ModelResolution;

use App\Services\ModelResolution\Contracts\ModelResolverInterface;
use App\Services\ModelResolution\DTOs\ResolvedModel;
use InvalidArgumentException;

final readonly class ModelResolutionService
{
    /**
     * @param  array<ModelResolverInterface>  $resolvers
     */
    public function __construct(
        private array $resolvers
    ) {
    }

    public function resolve(array $pathSegments): ResolvedModel
    {
        foreach ($this->resolvers as $resolver) {
            if ($resolver->canResolve($pathSegments)) {
                return $resolver->resolve($pathSegments);
            }
        }

        throw new InvalidArgumentException('No resolver found for given path segments');
    }
}
