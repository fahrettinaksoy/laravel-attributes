<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Category;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait CategoryField
{
    #[FormField(type: 'number', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'], ['category_id' => 'desc'], primaryKey: 'category_id')]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $category_id;

    #[FormField(type: 'text', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $uuid;

    #[FormField(type: 'text', sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $code;

    #[FormField(type: 'text', sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $name;

    #[FormField(type: 'textarea', sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $description;

    #[FormField(type: 'image', sort_order: 5)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $image_path;

    #[FormField(type: 'number', sort_order: 6)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $price;

    #[FormField(type: 'modal', relationship: [ 'type' => 'currency', 'route' => 'definition/localization/currency', 'fields' => [ 'id' => 'code', 'label' => 'name', ], ], sort_order: 7)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $currency_code;

    #[FormField(type: 'number', sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $stock;

    #[FormField(type: 'text', sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $sku;

    #[FormField(type: 'select', options: [ 'true' => 'active', 'false' => 'passive', ], sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $is_active;

    #[FormField(type: 'datetime', sort_order: 12)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $created_at;

    #[FormField(type: 'modal', relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $created_by;

    #[FormField(type: 'modal', relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $updated_by;

    #[FormField(type: 'datetime', sort_order: 15)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $updated_at;
}