<?php

declare(strict_types=1);

namespace App\Models\Cat\Product\Image;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait ImageField
{
    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'number', sort_order: 0)]
    protected string $product_image_id;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'text', sort_order: 1)]
    protected string $uuid;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'cat/product', 'fields' => ['id' => 'product_id', 'label' => 'name']], sort_order: 2)]
    protected int $product_id;

    #[ActionType(['show', 'store', 'update'])]
    #[TableColumn(['showing'])]
    #[FormField(type: 'file', sort_order: 3)]
    protected string $path;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering'])]
    #[FormField(type: 'text', sort_order: 4)]
    protected string $alt_text;

    #[ActionType(['index', 'show', 'store', 'update'])]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[FormField(type: 'number', sort_order: 5)]
    protected int $sort_order;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'sorting'])]
    #[FormField(type: 'datetime', sort_order: 6)]
    protected string $created_at;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'system/user', 'fields' => ['id' => 'created_by', 'label' => 'full_name']], sort_order: 7)]
    protected int $created_by;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'hiding'])]
    #[FormField(type: 'modal', relationship: ['type' => 'parent', 'route' => 'system/user', 'fields' => ['id' => 'updated_by', 'label' => 'full_name']], sort_order: 8)]
    protected int $updated_by;

    #[ActionType(['show'])]
    #[TableColumn(['showing', 'sorting'])]
    #[FormField(type: 'datetime', sort_order: 9)]
    protected string $updated_at;

}