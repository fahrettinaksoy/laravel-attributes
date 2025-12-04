<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Brand\Relations\BrandTranslation;

use App\Attributes\Model\ModuleOperation;
use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Definition\Catalog\Brand\BrandModel;
use App\Models\Definition\Localization\Language\LanguageModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.brand.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.brand.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.brand.brand.translation.delete', 'sort_order' => 3],
    ]
)]
class BrandTranslationModel extends BaseModel
{
    use BrandTranslationField;

    public $table = 'def_brand_translation';

    public $primaryKey = 'brand_translation_id';

    public string $defaultSorting = '-brand_translation_id';

    public array $allowedRelations = [
        'brand',
        'language',
    ];

    public function brand(): HasOne
    {
        return $this->hasOne(BrandModel::class, 'brand_id', 'brand_id');
    }

    public function language(): HasOne
    {
        return $this->hasOne(LanguageModel::class, 'code', 'language_code');
    }
}
