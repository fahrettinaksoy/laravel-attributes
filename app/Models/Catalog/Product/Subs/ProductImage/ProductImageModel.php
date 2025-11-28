<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Subs\ProductImage;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\Subs\ProductImage\ProductImageField;

use App\Models\Catalog\Product\ProductModel;
use App\Models\Catalog\Product\Subs\ProductImage\Subs\ProductImageTranslation\ProductImageTranslationModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.image.delete', 'sort_order' => 3],
    ]
)]
class ProductImageModel extends BaseModel
{
    use ProductImageField;

    public $table = 'cat_product_image';
    public $primaryKey = 'product_image_id';
    public string $defaultSorting = '-product_image_id';

    public array $allowedRelations = [
        'product',
        'translations',
    ];

    public function product(): HasOne
    {
        return $this->hasOne(ProductModel::class, 'product_id', 'product_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductImageTranslationModel::class, 'product_image_id', 'product_image_id');
    }
}
