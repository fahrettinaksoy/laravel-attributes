<?php

declare(strict_types=1);

namespace App\Services\ModuleValidation\DTO;

class ParsedMainDTO
{
    public function __construct(
        public string $modelClass,
        public string $tableName,
        public string $mainModelPath
    ) {}
}
