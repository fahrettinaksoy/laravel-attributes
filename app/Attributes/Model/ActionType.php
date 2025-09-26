<?php

declare(strict_types=1);

namespace App\Attributes\Model;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class ActionType
{
    const DEFAULT_ACTIONS = ['filter', 'show', 'store', 'update', 'destroy'];

    public function __construct(
        public array $actions = self::DEFAULT_ACTIONS,
    ) {}
}
