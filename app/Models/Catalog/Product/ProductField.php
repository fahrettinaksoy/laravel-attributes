<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait ProductField
{
    #[FormField(type: 'number', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['product_id' => 'desc'], primaryKey: 'product_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $product_id;

    #[FormField(type: 'text', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'text', required: true, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $name;

    #[FormField(type: 'textarea', sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $description;

    #[FormField(type: 'image', required: true, sort_order: 5)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $image_path;

    #[FormField(type: 'number', required: true, sort_order: 6)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?int $price;

    #[FormField(type: 'modal', relationship: [ 'type' => 'currency', 'route' => 'definition/localization/currency', 'fields' => [ 'id' => 'code', 'label' => 'name', ], ], required: true, sort_order: 7)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $currency_code;

    #[FormField(type: 'number', required: true, sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType([])]
    protected int $stock;

    #[FormField(type: 'text', required: true, sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $sku;

    #[FormField(type: 'select', relationship: [ 'type' => 'currency', 'route' => 'definition/catalog/category', 'fields' => [ 'id' => 'category_id', 'label' => 'name', ], ], required: true, sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $category_id;

    #[FormField(type: 'boolean', options: [ 'true' => 'active', 'false' => 'passive', ], required: true, sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?bool $status;

    #[FormField(type: 'datetime', sort_order: 12)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', sort_order: 15)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}