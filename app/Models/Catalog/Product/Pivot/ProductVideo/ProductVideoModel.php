<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivot\ProductVideo;

use App\Models\BaseModel;
use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVideoModel extends BaseModel
{
	use ProductVideoField;

    protected $table = 'product_video';
    protected $primaryKey = 'product_video_id';

    public string $defaultSorting = 'sort_order';

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'product_id');
    }
}
