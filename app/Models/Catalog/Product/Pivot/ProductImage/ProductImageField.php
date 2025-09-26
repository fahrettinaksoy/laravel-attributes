<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivot\ProductImage;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait ProductImageField
{
    #[FormField(type: 'number', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['desc'])]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $product_image_id;

    #[FormField(type: 'modal', sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected int $product_id;

    #[FormField(type: 'text', sort_order: 3)]
    #[TableColumn(['showing', 'filtering'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $image_path;

    #[FormField(type: 'text', sort_order: 4)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $alt_text;

    #[FormField(type: 'number', sort_order: 5)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected int $sort_order;

    #[FormField(type: 'datetime', sort_order: 6)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $created_at;

    #[FormField(type: 'datetime', sort_order: 7)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $updated_at;
}
