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

    #[FormField(type: 'text', required: false, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: true, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $code;

    #[FormField(type: 'text', required: true, sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $name;

    #[FormField(type: 'textarea', required: false, sort_order: 5)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $description;

    #[FormField(type: 'image', required: false, sort_order: 6)]
    #[TableColumn(['showing'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $image_path;

    #[FormField(type: 'text', required: false, sort_order: 7)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $symbol_left;

    #[FormField(type: 'text', required: false, sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $symbol_right;

    #[FormField(type: 'text', required: true, sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $decimal_place;

    #[FormField(type: 'text', required: true, sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $decimal_point;

    #[FormField(type: 'text', required: true, sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $thousand_point;

    #[FormField(type: 'text', required: true, sort_order: 12)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $value;

    #[FormField(type: 'text', required: false, sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $source;

    #[FormField(type: 'text', required: false, sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $last_synced_at;

    #[FormField(type: 'boolean', required: true, default: '1', options: ['true' => 'crypto', 'false' => 'fiat'], sort_order: 15)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected bool $is_crypto;

    #[FormField(type: 'boolean', required: true, default: '1', options: ['true' => 'active', 'false' => 'passive'], sort_order: 16)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected bool $status;

    #[FormField(type: 'datetime', required: false, sort_order: 17)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'created_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 18)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?int $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 19)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'updated_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 20)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?int $updated_by;
}
