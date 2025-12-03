<?php

declare(strict_types=1);

namespace App\Models\Catalog\Review;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait ReviewField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['review_id' => 'desc'], primaryKey: 'review_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $review_id;

    #[FormField(type: 'text', required: false, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: false, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'select', required: false, relationship: [ 'type' => 'product', 'route' => 'catalog/product', 'fields' => [ 'id' => 'product_id', 'label' => 'name', ], ], sort_order: 4)]
    #[TableColumn(['filtering', 'sorting', 'showing'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $product_id;

    #[FormField(type: 'select', required: false, relationship: [ 'type' => 'account', 'route' => 'accounting/account', 'fields' => [ 'id' => 'account_id', 'label' => 'name', ], ], sort_order: 5)]
    #[TableColumn(['filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $account_id;

    #[FormField(type: 'text', required: false, sort_order: 6)]
    #[TableColumn(['showing', 'filtering'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $author;

    #[FormField(type: 'textarea', required: false, sort_order: 7)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $content;

    #[FormField(type: 'number', required: false, sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $rating;

    #[FormField(type: 'boolean', required: false, options: [ 'true' => 'active', 'false' => 'passive', ], sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected bool $status;

    #[FormField(type: 'datetime', required: false, sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 11)]
    #[TableColumn(['filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 12)]
    #[TableColumn(['filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}
