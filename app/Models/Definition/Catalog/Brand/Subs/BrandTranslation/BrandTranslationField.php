<?php

declare(strict_types=1);

namespace App\Models\Definition\Catalog\Brand\Subs\BrandTranslation;

use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;
use App\Attributes\Model\ActionType;

trait BrandTranslationField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['brand_translation_id' => 'desc'], primaryKey: 'brand_translation_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $brand_translation_id;

    #[FormField(type: 'text', required: false, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: false, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'modal', required: true, relationship: ['type' => 'language', 'route' => 'definition/localization/language', 'fields' => ['id' => 'code', 'label' => 'name']], sort_order: 4)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $language_code;

    #[FormField(type: 'modal', required: true, relationship: ['type' => 'brand', 'route' => 'definition/catalog/brand', 'fields' => ['id' => 'brand_id', 'label' => 'name']], sort_order: 5)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $brand_id;

    #[FormField(type: 'text', required: true, sort_order: 6)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $name;

    #[FormField(type: 'textarea', required: false, sort_order: 7)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $summary;

    #[FormField(type: 'textarea', required: false, sort_order: 8)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $description;

    #[FormField(type: 'text', required: true, sort_order: 9)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $slug;

    #[FormField(type: 'text', required: false, sort_order: 10)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $meta_title;

    #[FormField(type: 'text', required: false, sort_order: 11)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $meta_description;

    #[FormField(type: 'text', required: false, sort_order: 12)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?string $meta_keyword;

    #[FormField(type: 'datetime', required: false, sort_order: 13)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'created_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 14)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?int $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 15)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'updated_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 16)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?int $updated_by;
}
