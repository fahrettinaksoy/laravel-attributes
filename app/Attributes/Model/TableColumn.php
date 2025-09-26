<?php

declare(strict_types=1);

namespace App\Attributes\Model;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TableColumn
{
    const DEFAULT_ACTIONS = [];

    public function __construct(
        public array $actions = self::DEFAULT_ACTIONS,
        public array $sorting = [],
        public string $primaryKey = '',
    ) {}
}
