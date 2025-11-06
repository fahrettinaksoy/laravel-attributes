<?php

declare(strict_types=1);

namespace App\Attributes\Model;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class ModuleOperation
{
    public function __construct(
        public array $items = [],
    ) {}
}
