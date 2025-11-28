<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Subs\ProductVideo\Subs\ProductVideoTranslation;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\Subs\ProductVideo\Subs\ProductVideoTranslation;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.delete', 'sort_order' => 3],
    ]
)]
class ProductVideoTranslationModel extends BaseModel
{
    use ProductVideoTranslationField;

    public $table = 'cat_product_image_translation';
    public $primaryKey = 'product_image_translation_id';
    public string $defaultSorting = '-product_image_translation_id';

    public array $allowedRelations = [
        'product',
    ];
}
