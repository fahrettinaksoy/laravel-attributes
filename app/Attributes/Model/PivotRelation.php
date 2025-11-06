<?php

declare(strict_types=1);

namespace App\Attributes\Model;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class PivotRelation
{
    public function __construct(
        public bool $enabled,
        public string $code,
        public ?int $sort_order = null,
    ) {}
}
