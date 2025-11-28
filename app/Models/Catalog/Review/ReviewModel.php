<?php

declare(strict_types=1);

namespace App\Models\Catalog\Review;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Review\ReviewField;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.review.delete', 'sort_order' => 3],
    ]
)]
class ReviewModel extends BaseModel
{
    use ReviewField;

    public $table = 'cat_review';

    public $primaryKey = 'review_id';

    public string $defaultSorting = '-review_id';
}
