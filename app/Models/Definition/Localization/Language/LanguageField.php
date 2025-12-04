<?php

declare(strict_types=1);

namespace App\Models\Definition\Localization\Language;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait LanguageField
{
    #[FormField(type: 'number', required: false, sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['language_id' => 'desc'], primaryKey: 'language_id')]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $language_id;

    #[FormField(type: 'text', required: false, sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected string $uuid;

    #[FormField(type: 'text', required: false, sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $code;

    #[FormField(type: 'text', required: false, sort_order: 4)]
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
    protected string $flag_path;

    #[FormField(type: 'select', required: false, options: ['ltr' => 'Left-To-Right', 'rtl' => 'Right-To-Left'], sort_order: 7)]
    #[TableColumn(['filtering'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $direction;

    #[FormField(type: 'text', required: false, sort_order: 8)]
    #[TableColumn(['filtering'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $directory;

    #[FormField(type: 'text', required: false, sort_order: 9)]
    #[TableColumn(['filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected string $locale;

    #[FormField(type: 'number', required: false, sort_order: 10)]
    #[TableColumn(['sorting', 'filtering'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected int $sort_order;

    #[FormField(type: 'boolean', required: false, options: ['true' => 'active', 'false' => 'passive'], sort_order: 11)]
    #[TableColumn(['sorting', 'filtering', 'showing'])]
    #[ActionType(['index', 'show', 'store', 'update'])]
    protected ?bool $status;

    #[FormField(type: 'datetime', required: false, sort_order: 12)]
    #[TableColumn(['sorting', 'filtering', 'showing'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'created_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 13)]
    #[TableColumn(['sorting', 'filtering', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $created_by;

    #[FormField(type: 'datetime', required: false, sort_order: 15)]
    #[TableColumn(['sorting', 'filtering', 'showing'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_at;

    #[FormField(type: 'modal', required: false, relationship: ['name' => 'updated_by', 'route' => 'system/user', 'fields' => ['id' => 'user_id', 'label' => 'first_name']], sort_order: 14)]
    #[TableColumn(['sorting', 'filtering', 'hiding'])]
    #[ActionType(['index', 'show'])]
    protected ?string $updated_by;
}
