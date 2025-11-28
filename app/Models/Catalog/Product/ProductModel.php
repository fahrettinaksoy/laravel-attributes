<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\ProductField;

use App\Models\Definition\Localization\Currency\CurrencyModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Definition\Catalog\Category\CategoryModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Catalog\Product\Subs\ProductTranslation\ProductTranslationModel;
use App\Models\Catalog\Product\Subs\ProductImage\ProductImageModel;
use App\Models\Catalog\Product\Subs\ProductVideo\ProductVideoModel;

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

    public array $allowedRelations = [
        'currency',
        'category',
        'images',
        'translations',
        'videos',
    ];

    public function currency(): HasOne
    {
        return $this->hasOne(CurrencyModel::class, 'code', 'currency_code');
    }

    public function category(): HasOne
    {
        return $this->hasOne(CategoryModel::class, 'category_id', 'category_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id', 'product_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(ProductTranslationModel::class, 'product_id', 'product_id');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(ProductVideoModel::class, 'product_id', 'product_id');
    }

}
