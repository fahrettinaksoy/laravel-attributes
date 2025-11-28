<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Relations\ProductTranslation;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\Relations\ProductTranslation\ProductTranslationField;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.translation.delete', 'sort_order' => 3],
    ]
)]
class ProductTranslationModel extends BaseModel
{
    use ProductTranslationField;

    public $table = 'cat_product_translation';
    public $primaryKey = 'product_translation_id';
    public string $defaultSorting = '-product_translation_id';

    public array $allowedRelations = [
        'product',
    ];

    public function product(): HasOne
    {
        return $this->hasOne(ProductModel::class, 'product_id', 'product_id');
    }

}