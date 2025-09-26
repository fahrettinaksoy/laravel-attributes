<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivot\ProductTranslation;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductTranslationModel extends BaseModel
{
	use ProductTranslationField;

    protected $table = 'product_translation';
    protected $primaryKey = 'product_translation_id';

    public string $defaultSorting = '-product_translation_id';

    public function productTranslations(): HasMany
    {
        return $this->hasMany(ProductTranslationModel::class, 'language_code', 'code');
    }
}
