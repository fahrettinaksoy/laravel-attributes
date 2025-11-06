<?php

declare(strict_types=1);

namespace App\Models\Cat\Product;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Cat\Product\ProductField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class ProductModel extends BaseModel
{
    use ProductField;

    public $table = 'cat_product';
    public $primaryKey = 'product_id';
    public string $defaultSorting = '-product_id';
    public array $allowedRelations = ['images', 'videos', 'translations'];

    public function images(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\Cat\Product\Image\ImageModel::class,
            'product_id',
            'product_id'
        );
    }

    public function videos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\Cat\Product\Video\VideoModel::class,
            'product_id',
            'product_id'
        );
    }

    public function translations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\Cat\Product\Translation\TranslationModel::class,
            'product_id',
            'product_id'
        );
    }
}