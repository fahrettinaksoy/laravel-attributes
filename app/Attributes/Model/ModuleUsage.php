<?php

declare(strict_types=1);

namespace App\Attributes\Model;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ModuleUsage
{
    public function __construct(
        public bool $enabled = true,
        public ?int $sort_order = null,
    ) {}
}
