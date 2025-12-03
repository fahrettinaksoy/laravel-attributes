<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\DTO;

class ResolvedModuleDTO
{
    public function __construct(
        public bool $isPivotRoute,
        public string $modelClass,
        public string $tableName,
        public string $mainModelPath,
        public string $fullPath,
        public ?string $parentModelClass = null,
        public ?string $pivotModelClass = null,
        public ?string $relationName = null,
        public ?string $originalRelationName = null,
        public ?int $parentId = null,
        public ?int $relationId = null,
        public ?string $fullPathWithIds = null,
        public ?string $pivotTableName = null,
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
