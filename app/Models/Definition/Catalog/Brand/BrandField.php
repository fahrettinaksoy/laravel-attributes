<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Brand;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait BrandField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['brand_id' => 'desc'], primaryKey: 'brand_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $brand_id;

    #[FormField(type: 'text', required: false, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: true, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'text', required: true, sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $name;

    #[FormField(type: 'textarea', required: false, sort_order: 5)]
    #[TableColumn(['filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $description;

    #[FormField(type: 'image', required: false, sort_order: 6)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $image_path;

    #[FormField(type: 'modal', required: false, relationship: [ 'type' => 'brand', 'route' => 'definition/catalog/brand', 'fields' => [ 'id' => 'brand_id', 'label' => 'name', ], ], sort_order: 7)]
    #[TableColumn(['filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $parent_id;

    #[FormField(type: 'number', required: false, sort_order: 8)]
    #[TableColumn(['filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $layout_id;

    #[FormField(type: 'number', required: false, sort_order: 9)]
    #[TableColumn(['filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $membership;

    #[FormField(type: 'boolean', required: false, options: [ 'true' => 'active', 'false' => 'passive', ], sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected bool $status;

    #[FormField(type: 'datetime', required: false, sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 12)]
    #[TableColumn(['filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 13)]
    #[TableColumn(['filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}
