<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Resolvers;

use App\Services\ModelResolution\Contracts\ModelResolverInterface;
use App\Services\ModelResolution\DTOs\ResolvedModel;
use App\Services\ModelResolution\Support\ModelClassBuilder;
use App\Services\ModelResolution\Support\PivotRouteParser;
use App\Services\ModelResolution\Support\RelationResolver;
use InvalidArgumentException;

final readonly class PivotRouteResolver implements ModelResolverInterface
{
    public function __construct(
        private ModelClassBuilder $classBuilder,
        private PivotRouteParser $parser,
        private RelationResolver $relationResolver,
    ) {
    }

    public function canResolve(array $pathSegments): bool
    {
        if (count($pathSegments) < 3) {
            return false;
        }

        return $this->parser->isPivotRoute($pathSegments);
    }

    public function resolve(array $pathSegments): ResolvedModel
    {
        $pivotInfo = $this->parser->parse($pathSegments);

        $mainModelClass = $this->resolveMainModel($pathSegments, $pivotInfo);

        $relationMethod = $pivotInfo->relationMethod;
        $relatedModel = $this->relationResolver->resolveRelation($mainModelClass, $relationMethod);

        $fullPathWithIds = $this->parser->buildFullPathWithIds($pathSegments);

        return new ResolvedModel(
            modelClass: get_class($relatedModel),
            tableName: end($pivotInfo->parentModelPath),
            mainModelPath: implode('/', $pivotInfo->parentModelPath),
            fullPath: implode('/', $pathSegments),
            isPivotRoute: true,
            parentModelClass: $mainModelClass,
            pivotModelClass: get_class($relatedModel),
            relationName: $relationMethod,
            originalRelationName: $pivotInfo->originalRelation,
            parentId: $pivotInfo->parentId,
            relationId: $pivotInfo->relationId,
            pivotTableName: $relatedModel->getTable(),
            fullPathWithIds: implode('/', $fullPathWithIds),
        );
    }

    private function resolveMainModel(array $pathSegments, object $pivotInfo): string
    {
        $parentModelPath = $pivotInfo->parentModelPath;
        $parentModelEndIndex = $pivotInfo->parentModelEndIndex;
        $parentIdIndex = $pivotInfo->parentIdIndex;

        if ($parentModelEndIndex < $parentIdIndex && count($parentModelPath) > 0) {
            $baseModelClass = $this->classBuilder->build($parentModelPath);
            $intermediateRelation = $this->findIntermediateRelation(
                $pathSegments,
                $parentModelEndIndex,
                $parentIdIndex
            );

            if ($intermediateRelation) {
                return $this->relationResolver->resolveRelationClass($baseModelClass, $intermediateRelation);
            }

            throw new InvalidArgumentException('Cannot resolve intermediate relation');
        }

        return $this->classBuilder->build($parentModelPath);
    }

    private function findIntermediateRelation(array $pathSegments, int $start, int $end): ?string
    {
        for ($i = $start + 1; $i < $end; $i++) {
            if (!is_numeric($pathSegments[$i])) {
                return \Illuminate\Support\Str::snake($pathSegments[$i]);
            }
        }

        return null;
    }
}
