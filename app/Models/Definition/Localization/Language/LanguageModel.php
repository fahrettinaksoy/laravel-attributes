<?php

declare(strict_types=1);

namespace App\Models\Definition\Localization\Language;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Definition\Localization\Language\LanguageField;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.delete', 'sort_order' => 3],
    ]
)]
class LanguageModel extends BaseModel
{
    use LanguageField;

    public $table = 'def_loc_language';
    public $primaryKey = 'language_id';
    public string $defaultSorting = '-language_id';

    public array $allowedRelations = [];

}
