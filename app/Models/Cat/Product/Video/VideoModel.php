<?php

declare(strict_types=1);

namespace App\Models\Cat\Product\Video;

use App\Attributes\Model\ModuleUsage;
use App\Models\BaseModel;
use App\Models\Cat\Product\Video\VideoField;

#[ModuleUsage(enabled: true, sort_order: 1)]
class VideoModel extends BaseModel
{
    use VideoField;

    public $table = 'cat_product_video';
    public $primaryKey = 'video_id';
    public string $defaultSorting = '-video_id';
    public array $allowedRelations = [];
}