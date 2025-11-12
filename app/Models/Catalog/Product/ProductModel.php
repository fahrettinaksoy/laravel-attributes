<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.delete', 'sort_order' => 3],
        ['code' => 'copy', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.copy', 'sort_order' => 4],
        ['code' => 'close', 'plural' => true, 'singular' => true, 'route_name' => 'catalog.product.product.close', 'sort_order' => 5],
    ],
)]
class ProductModel extends BaseModel
{
    public $table = 'cat_product';
    public $primaryKey = 'product_id';
    public string $defaultSorting = '-product_id';
}