<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Resolvers;

use App\Services\ModelResolution\Contracts\ModelResolverInterface;
use App\Services\ModelResolution\DTOs\ResolvedModel;
use App\Services\ModelResolution\Support\ModelClassBuilder;
use InvalidArgumentException;

final readonly class MainRouteResolver implements ModelResolverInterface
{
    public function __construct(
        private ModelClassBuilder $classBuilder
    ) {
    }

    public function canResolve(array $pathSegments): bool
    {
        // Pivot route değilse bu resolver kullanılır
        return !$this->isPivotRoute($pathSegments);
    }

    public function resolve(array $pathSegments): ResolvedModel
    {
        $modelPath = $this->extractModelPath($pathSegments);

        if (empty($modelPath)) {
            throw new InvalidArgumentException('Empty model path');
        }

        $modelClass = $this->classBuilder->build($modelPath);

        return new ResolvedModel(
            modelClass: $modelClass,
            tableName: end($modelPath),
            mainModelPath: implode('/', $modelPath),
            fullPath: implode('/', $pathSegments),
            isPivotRoute: false,
        );
    }

    private function extractModelPath(array $pathSegments): array
    {
        if (is_numeric(end($pathSegments))) {
            return array_slice($pathSegments, 0, -1);
        }

        return $pathSegments;
    }

    private function isPivotRoute(array $pathSegments): bool
    {
        if (count($pathSegments) < 3) {
            return false;
        }

        foreach ($pathSegments as $i => $segment) {
            if (
                is_numeric($segment)
                && isset($pathSegments[$i + 1])
                && !is_numeric($pathSegments[$i + 1])
                && preg_match('/^[a-zA-Z_-]+$/', $pathSegments[$i + 1])
            ) {
                return true;
            }
        }

        return false;
    }
}
