<?php

declare(strict_types=1);

namespace App\Models\Def\Cat\Language;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Def\Cat\Language\LanguageField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class LanguageModel extends BaseModel
{
    use LanguageField;

    public $table = 'def_cat_language';
    public $primaryKey = 'language_id';
    public string $defaultSorting = '-language_id';
    public array $allowedRelations = [];
}