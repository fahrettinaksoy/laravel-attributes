<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\DTO;

class ParsedPivotDTO
{
    public function __construct(
        public string $parentModelClass,
        public string $originalRelation,
        public int $parentId,
        public ?int $relationId,
        public string $mainModelPath
    ) {}
}
