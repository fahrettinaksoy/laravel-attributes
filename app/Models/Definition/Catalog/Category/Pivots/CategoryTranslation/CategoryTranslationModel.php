<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Category\Pivots\CategoryTranslation;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Definition\Catalog\Category\Pivots\CategoryTranslation\CategoryTranslationField;

#[ModuleUsage(enabled: true, sort_order: 2)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.delete', 'sort_order' => 2],
    ]
)]
class CategoryTranslationModel extends BaseModel
{
    use CategoryTranslationField;

    public $table = 'def_cat_category_translation';
    public $primaryKey = 'category_translation_id';
    public string $defaultSorting = '-category_translation_id';
}