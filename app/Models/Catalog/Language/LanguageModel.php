<?php

declare(strict_types=1);

namespace App\Models\Catalog\Language;

use App\Models\BaseModel;
use App\Models\Catalog\Product\Pivot\ProductTranslation\ProductTranslationModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LanguageModel extends BaseModel
{
    use LanguageField;

    public $table = 'language';

    public $primaryKey = 'language_id';

    public string $defaultSorting = '-language_id';

    protected static function boot(): void
    {
        parent::boot();
    }

    public function productTranslations(): HasMany
    {
        return $this->hasMany(ProductTranslationModel::class, 'language_code', 'code');
    }
}
