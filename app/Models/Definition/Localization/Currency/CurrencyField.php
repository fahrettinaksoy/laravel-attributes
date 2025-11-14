<?php

declare(strict_types=1);

namespace App\Models\Definition\Localization\Currency;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait CurrencyField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['currency_id' => 'desc'], primaryKey: 'currency_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $currency_id;

    #[FormField(type: 'text', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: false, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'text', required: true, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $name;

    #[FormField(type: 'textarea', required: false, sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $description;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $image_path;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $symbol_left;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $symbol_right;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $decimal_place;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $decimal_point;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $thousand_point;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $value;

    #[FormField(type: 'textarea', required: false, sort_order: 4)]
    #[TableColumn([])]
    #[ActionType([])]
    protected string $source;

    #[FormField(type: 'textarea', required: false, sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $last_synced_at;

    #[FormField(type: 'boolean', required: true, default: '', options: [ 'true' => 'yes', 'false' => 'no', ], sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?bool $is_crypto;

    #[FormField(type: 'boolean', required: true, default: '1', options: [ 'true' => 'active', 'false' => 'passive', ], sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?bool $status;

    #[FormField(type: 'datetime', required: false, sort_order: 12)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 15)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}