<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Models\BaseModel;
use App\Models\Catalog\Category\CategoryModel;
use App\Models\Catalog\Product\Pivot\ProductImage\ProductImageModel;
use App\Models\Catalog\Product\Pivot\ProductTranslation\ProductTranslationModel;
use App\Models\Catalog\Product\Pivot\ProductVideo\ProductVideoModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductModel extends BaseModel
{
    use ProductField;

    public $table = 'product';

    public $primaryKey = 'product_id';

    public string $defaultSorting = '-product_id';

    public array $allowedRelations = [
        'product_translations',
        'product_images',
        'product_videos',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (ProductModel $product) {
            $fill = [];
            $fill['code'] = Str::upper(Str::random(8));

            $product->forceFill($fill);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CategoryModel::class, 'category_id', 'category_id');
    }

    public function product_translations(): HasMany
    {
        return $this->hasMany(ProductTranslationModel::class, 'product_id', 'product_id');
    }

    public function product_images(): HasMany
    {
        return $this->hasMany(ProductImageModel::class, 'product_id', 'product_id')->orderBy('sort_order');
    }

    public function product_videos(): HasMany
    {
        return $this->hasMany(ProductVideoModel::class, 'product_id', 'product_id')->orderBy('sort_order');
    }

    public function translation(string $languageCode): HasMany
    {
        return $this->translations()->where('language_code', $languageCode);
    }
}
