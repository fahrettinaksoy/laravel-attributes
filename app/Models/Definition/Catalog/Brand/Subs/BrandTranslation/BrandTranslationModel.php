<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Brand\Subs\BrandTranslation;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Definition\Catalog\Brand\Subs\BrandTranslation\BrandTranslationField;

use App\Models\Definition\Localization\Language\LanguageModel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Models\Definition\Catalog\Brand\BrandModel;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.category.translation.delete', 'sort_order' => 3],
    ]
)]
class BrandTranslationModel extends BaseModel
{
    use BrandTranslationField;

    public $table = 'def_category_translation';
    public $primaryKey = 'category_translation_id';
    public string $defaultSorting = '-category_translation_id';

    public array $allowedRelations = [
        'language',
        'category',
    ];

    public function language(): HasOne
    {
        return $this->hasOne(LanguageModel::class, 'code', 'language_code');
    }

    public function category(): HasOne
    {
        return $this->hasOne(BrandModel::class, 'category_id', 'category_id');
    }

}
