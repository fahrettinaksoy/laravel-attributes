<?php

declare(strict_types=1);

namespace App\Models\Catalog\Category;

use App\Models\BaseModel;
use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CategoryModel extends BaseModel
{
    use CategoryField;

    public $table = 'category';

    public $primaryKey = 'category_id';

    public string $defaultSorting = '-category_id';

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (CategoryModel $category) {
            $fill = [];
            $fill['code'] = Str::upper(Str::random(8));

            $category->forceFill($fill);
        });
    }

    public function products(): HasMany
    {
        return $this->hasMany(ProductModel::class, 'category_id', 'category_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'category_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id');
    }

    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }
}
