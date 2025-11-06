<?php

declare(strict_types=1);

namespace App\Models\Cat\Product;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait ProductField
{
    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'number', sort_order: 0)]
    protected string $product_id;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'text', sort_order: 1)]
    protected string $uuid;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'text', sort_order: 2)]
    protected string $name;

    #[ActionType(['index', 'show'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'text', sort_order: 3)]
    protected string $code;

    #[ActionType(['show', 'store', 'update'])]
    #[TableColumn(['showing'])]
    #[FormField(type: 'textarea', sort_order: 4)]
    protected string $description;

    #[ActionType(['show', 'store', 'update'])]
    #[TableColumn(['showing'])]
    #[FormField(type: 'file', sort_order: 5)]
    protected string $image;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'number', sort_order: 6)]
    protected float $price;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering'])]
    #[FormField(type: 'text', sort_order: 7)]
    protected string $currency_code;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'number', sort_order: 8)]
    protected int $stock;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'text', sort_order: 9)]
    protected string $sku;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'cat/category', 'fields' => ['id' => 'category_id', 'label' => 'name']], sort_order: 10)]
    protected int $category_id;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'checkbox', sort_order: 11)]
    protected bool $is_active;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'sorting'])]
    #[FormField(type: 'datetime', sort_order: 12)]
    protected string $created_at;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'system/user', 'fields' => ['id' => 'created_by', 'label' => 'full_name']], sort_order: 13)]
    protected int $created_by;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'system/user', 'fields' => ['id' => 'updated_by', 'label' => 'full_name']], sort_order: 14)]
    protected int $updated_by;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'sorting'])]
    #[FormField(type: 'datetime', sort_order: 15)]
    protected string $updated_at;

}