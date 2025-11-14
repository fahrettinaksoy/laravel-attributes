<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Category;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Definition\Catalog\Category\CategoryField;

use App\Models\Definition\Catalog\Category\Pivots\CategoryTranslation\CategoryTranslationModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.catalog.category.delete', 'sort_order' => 3],
    ]
)]
class CategoryModel extends BaseModel
{
    use CategoryField;

    public $table = 'def_cat_category';
    public $primaryKey = 'category_id';
    public string $defaultSorting = '-category_id';

    public array $allowedRelations = [
        'translations',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslationModel::class, 'category_id', 'category_id');
    }

}