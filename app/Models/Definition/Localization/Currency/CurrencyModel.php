<?php

declare(strict_types=1);

namespace App\Models\Definition\Localization\Currency;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Definition\Localization\Currency\CurrencyField;


#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.currency.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.currency.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'definition.localization.currency.delete', 'sort_order' => 3],
    ]
)]
class CurrencyModel extends BaseModel
{
    use CurrencyField;

    public $table = 'def_loc_currency';
    public $primaryKey = 'currency_id';
    public string $defaultSorting = '-currency_id';

    public array $allowedRelations = [];


}