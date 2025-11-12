<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Category;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.delete', 'sort_order' => 2],
    ],
)]
class CategoryModel extends BaseModel
{
    public $table = 'def_cat_category';
    public $primaryKey = 'category_id';
    public string $defaultSorting = '-category_id';
}