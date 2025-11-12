<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivots\ProductVideo;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

#[ModuleUsage(enabled: true, sort_order: 4)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.delete', 'sort_order' => 2],
    ],
)]
class ProductVideoModel extends BaseModel
{
    public $table = 'cat_product_video';
    public $primaryKey = 'product_video_id';
    public string $defaultSorting = '-product_video_id';
}