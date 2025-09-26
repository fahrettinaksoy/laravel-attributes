<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\SetFieldFromAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasFactory;
    use SetFieldFromAttributes;

    public $fillable = [];

    public $keyType = 'int';

    public array $allowedFiltering = [];

    public array $allowedSorting = [];

    public array $allowedShowing = [];

    public array $allowedRelations = [];

    public array $defaultRelations = [];

    public string $defaultSorting = '-id';

    public $incrementing = true;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = 'updated_at';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setFillableFromAttributes();
        $this->setTableColumnsFromAttributes();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {

        });

        static::updating(function ($model) {

        });
    }
}
