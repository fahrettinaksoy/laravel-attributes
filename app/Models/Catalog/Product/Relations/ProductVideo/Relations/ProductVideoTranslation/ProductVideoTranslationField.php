<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait ProductVideoTranslationField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['product_video_translation_id' => 'desc'], primaryKey: 'product_video_translation_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $product_video_translation_id;

    #[FormField(type: 'modal', required: false, sort_order: 2)]
    #[TableColumn([])]
    #[ActionType(['index', 'show'])]
    protected string $product_video_id;

    #[FormField(type: 'text', required: false, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: false, sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'text', required: false, sort_order: 5)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $name;

    #[FormField(type: 'textarea', required: false, sort_order: 6)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $summary;

    #[FormField(type: 'textarea', required: false, sort_order: 7)]
    #[TableColumn([])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $description;

    #[FormField(type: 'datetime', required: false, sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'created_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: [ 'name' => 'updated_by', 'route' => 'system/user', 'fields' => [ 'id' => 'user_id', 'label' => 'first_name', ], ], sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}
