<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\Support;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

final readonly class RelationResolver
{
    public function resolveRelation(string $modelClass, string $relationMethod): Model
    {
        $model = new $modelClass;

        if (!method_exists($model, $relationMethod)) {
            throw new InvalidArgumentException(
                "Relation '{$relationMethod}' not defined on {$modelClass}"
            );
        }

        $relationObj = $model->{$relationMethod}();

        return $relationObj->getRelated();
    }

    public function resolveRelationClass(string $modelClass, string $relationMethod): string
    {
        $relatedModel = $this->resolveRelation($modelClass, $relationMethod);

        return get_class($relatedModel);
    }
}
