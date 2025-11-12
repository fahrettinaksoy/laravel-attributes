<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivots\ProductImage;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

#[ModuleUsage(enabled: true, sort_order: 3)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.delete', 'sort_order' => 2],
    ],
)]
class ProductImageModel extends BaseModel
{
    public $table = 'cat_product_image';
    public $primaryKey = 'product_image_id';
    public string $defaultSorting = '-product_image_id';
}