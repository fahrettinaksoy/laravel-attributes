<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation\ProductVideoTranslationField;

use App\Models\Catalog\ProductVideo\ProductVideoModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.translation.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.translation.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.translation.delete', 'sort_order' => 3],
    ]
)]
class ProductVideoTranslationModel extends BaseModel
{
    use ProductVideoTranslationField;

    public $table = 'cat_product_video_translation';
    public $primaryKey = 'product_video_translation_id';
    public string $defaultSorting = '-product_video_translation_id';

    public array $allowedRelations = [
        'productVideo',
    ];

    public function productVideo(): HasOne
    {
        return $this->hasOne(ProductVideoModel::class, 'product_video_id', 'product_video_id');
    }

}