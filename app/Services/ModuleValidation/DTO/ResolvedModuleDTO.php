<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\DTO;

class ResolvedModuleDTO
{
    public function __construct(
        public bool $isPivotRoute,
        public string $modelClass,
        public string $tableName,
        public ?string $parentModelClass,
        public ?string $pivotModelClass,
        public ?string $relationName,
        public ?string $originalRelationName,
        public ?int $parentId,
        public ?int $relationId,
        public string $mainModelPath,
        public string $fullPath
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
