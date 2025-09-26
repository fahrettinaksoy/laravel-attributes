<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait ProductField
{
    #[FormField(type: 'number', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['desc'])]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $product_id;

    #[FormField(type: 'text', sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $name;

    #[FormField(type: 'text', sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $code;

    #[FormField(type: 'textarea', sort_order: 4)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $description;

    #[FormField(type: 'text', sort_order: 5)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $image;

    #[FormField(type: 'number', sort_order: 6)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected float $price;

    #[FormField(type: 'text', sort_order: 7)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $currency;

    #[FormField(type: 'number', sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected int $stock;

    #[FormField(type: 'text', sort_order: 9)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $sku;

    #[FormField(type: 'modal', sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?int $category_id;

    #[FormField(type: 'checkbox', sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected bool $status;

    #[FormField(type: 'datetime', sort_order: 12)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $created_at;

    #[FormField(type: 'datetime', sort_order: 13)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $updated_at;
}
