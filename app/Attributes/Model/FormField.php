<?php

declare(strict_types=1);

namespace App\Attributes\Model;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class FormField
{
    public function __construct(
        public string $type,
        public bool $required = false,
        public string $default = '',
        public string $value = '',
        public array $relationship = [],
        public array $options = [],
        public int $sort_order = 0,
    ) {}
}
