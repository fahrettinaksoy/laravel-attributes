<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\ProductField;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.delete', 'sort_order' => 3],
        ['code' => 'close', 'plural' => true, 'singular' => true, 'route_name' => 'catalog.product.close', 'sort_order' => 5],
        ['code' => 'copy', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.copy', 'sort_order' => 4],
    ]
)]
class ProductModel extends BaseModel
{
    use ProductField;

    public $table = 'cat_product';
    public $primaryKey = 'product_id';
    public string $defaultSorting = '-product_id';
}