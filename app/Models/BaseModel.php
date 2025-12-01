<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\System\User\UserModel;
use App\Traits\SetFieldFromAttributes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

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
            if (auth()->check()) {
                $fill = [];
                $fill['created_by'] = auth()->id();
                $fill['updated_by'] = auth()->id();
                $model->forceFill($fill);
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $fill = [];
                $fill['updated_by'] = auth()->id();
                $model->forceFill($fill);
            }
        });
    }

    public function created_by(): HasOne
    {
        return $this->hasOne(UserModel::class, 'created_by', 'user_id');
    }

    public function updated_by(): HasOne
    {
        return $this->hasOne(UserModel::class, 'updated_by', 'user_id');
    }

    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }

    protected function createdAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? $this->formatDateTimeByCurrentLocale($value) : null,
        );
    }

    protected function updatedAt(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $value ? $this->formatDateTimeByCurrentLocale($value) : null,
        );
    }
}
