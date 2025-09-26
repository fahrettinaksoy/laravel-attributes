<?php

declare(strict_types=1);

namespace App\Models\Catalog\Language;

use App\Attributes\Model\ActionType;
use App\Attributes\Model\FormField;
use App\Attributes\Model\TableColumn;

trait LanguageField
{
    #[FormField(type: 'number', sort_order: 1)]
    #[TableColumn(['showing', 'filtering', 'sorting'], ['desc'])]
    #[ActionType(['index', 'show', 'destroy'])]
    protected int $language_id;

    #[FormField(type: 'text', sort_order: 2)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $code;

    #[FormField(type: 'text', sort_order: 3)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected string $name;

    #[FormField(type: 'text', sort_order: 4)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $locale;

    #[FormField(type: 'text', sort_order: 5)]
    #[TableColumn(['showing', 'hiding'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected ?string $flag;

    #[FormField(type: 'number', sort_order: 6)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected int $sort_order;

    #[FormField(type: 'checkbox', sort_order: 7)]
    #[TableColumn(['showing', 'filtering', 'sorting'])]
    #[ActionType(['index', 'show', 'store', 'update', 'destroy'])]
    protected bool $status;

    #[FormField(type: 'datetime', sort_order: 8)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $created_at;

    #[FormField(type: 'datetime', sort_order: 9)]
    #[TableColumn(['showing', 'hiding', 'sorting'])]
    #[ActionType(['index', 'show'])]
    protected string $updated_at;
}
