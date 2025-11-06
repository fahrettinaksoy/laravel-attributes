<?php

declare(strict_types=1);

namespace App\Models\Cat\Product\Image;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Cat\Product\Image\ImageField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class ImageModel extends BaseModel
{
    use ImageField;

    public $table = 'cat_product_image';
    public $primaryKey = 'image_id';
    public string $defaultSorting = '-image_id';
    public array $allowedRelations = [];
}