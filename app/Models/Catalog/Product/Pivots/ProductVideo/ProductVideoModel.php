<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivots\ProductVideo;

use App\Models\BaseModel;
use App\Attributes\Model\ModuleUsage;
use App\Attributes\Model\ModuleOperation;
use App\Models\Catalog\Product\Pivots\ProductVideo\ProductVideoField;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ModuleUsage(enabled: true, sort_order: 1)]
#[ModuleOperation(
    items: [
        ['code' => 'active', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.active', 'sort_order' => 1],
        ['code' => 'passive', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.passive', 'sort_order' => 2],
        ['code' => 'delete', 'plural' => true, 'singular' => false, 'route_name' => 'catalog.product.product.video.delete', 'sort_order' => 3],
    ]
)]
class ProductVideoModel extends BaseModel
{
    use ProductVideoField;

    public $table = 'cat_product_video';
    public $primaryKey = 'product_video_id';
    public string $defaultSorting = '-product_video_id';

    public array $allowedRelations = [
        'product',
    ];

    public function product(): HasOne
    {
        return $this->hasOne(ProductModel::class, 'product_id', 'product_id');
    }

}