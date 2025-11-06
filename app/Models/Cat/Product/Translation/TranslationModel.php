<?php

declare(strict_types=1);

namespace App\Models\Cat\Product\Translation;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Cat\Product\Translation\TranslationField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class TranslationModel extends BaseModel
{
    use TranslationField;

    public $table = 'cat_product_translation';
    public $primaryKey = 'translation_id';
    public string $defaultSorting = '-translation_id';
    public array $allowedRelations = [];
}