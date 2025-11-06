<?php

declare(strict_types=1);

namespace App\Models\Def\Cat\Category;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Def\Cat\Category\CategoryField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class CategoryModel extends BaseModel
{
    use CategoryField;

    public $table = 'def_cat_category';
    public $primaryKey = 'category_id';
    public string $defaultSorting = '-category_id';
    public array $allowedRelations = ['products'];

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(
            \App\Models\Cat\Product\ProductModel::class,
            'category_id',
            'category_id'
        );
    }
}