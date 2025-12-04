<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Brand;

use App\Attributes\Model\ModuleOperation;
use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Definition\Catalog\Brand\Relations\BrandTranslation\BrandTranslationModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.delete', 'sort_order' => 3],
    ]
)]
class BrandModel extends BaseModel
{
    use BrandField;

    public $table = 'def_cat_brand';

    public $primaryKey = 'brand_id';

    public string $defaultSorting = '-brand_id';

    public array $allowedRelations = [
        'brand',
        'translations',
    ];

    public function brand(): HasOne
    {
        return $this->hasOne(BrandModel::class, 'brand_id', 'parent_id');
    }

    public function translations(): HasMany
    {
        return $this->hasMany(BrandTranslationModel::class, 'brand_id', 'brand_id');
    }
}
