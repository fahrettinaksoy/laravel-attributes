<?php

declare(strict_types=1);

namespace App\Services\ModelResolution\DTOs;

use Illuminate\Contracts\Support\Arrayable;

final readonly class ResolvedModel implements Arrayable
{
    public function __construct(
        public string $modelClass,
        public string $tableName,
        public string $mainModelPath,
        public string $fullPath,
        public bool $isPivotRoute = false,
        public ?string $parentModelClass = null,
        public ?string $pivotModelClass = null,
        public ?string $relationName = null,
        public ?string $originalRelationName = null,
        public ?int $parentId = null,
        public ?int $relationId = null,
        public ?string $pivotTableName = null,
        public ?string $fullPathWithIds = null,
    ) {
    }

    public function toArray(): array
    {
        return array_filter([
            'isPivotRoute' => $this->isPivotRoute,
            'modelClass' => $this->modelClass,
            'tableName' => $this->tableName,
            'mainModelPath' => $this->mainModelPath,
            'fullPath' => $this->fullPath,
            'parentModelClass' => $this->parentModelClass,
            'pivotModelClass' => $this->pivotModelClass,
            'relationName' => $this->relationName,
            'originalRelationName' => $this->originalRelationName,
            'parentId' => $this->parentId,
            'relationId' => $this->relationId,
            'pivotTableName' => $this->pivotTableName,
            'fullPathWithIds' => $this->fullPathWithIds,
        ], fn ($value) => $value !== null);
    }
}
