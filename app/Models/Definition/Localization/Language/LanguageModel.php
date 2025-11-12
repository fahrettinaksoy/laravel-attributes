<?php

declare(strict_types=1);

namespace App\Models\Definition\Localization\Language;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.language.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.language.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.language.language.delete', 'sort_order' => 2],
    ],
)]
class LanguageModel extends BaseModel
{
    public $table = 'def_cat_language';
    public $primaryKey = 'language_id';
    public string $defaultSorting = '-language_id';
}