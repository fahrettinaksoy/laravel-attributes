<?php

declare(strict_types=1);

namespace App\Models\Catalog\Product\Pivot\ProductImage;

use App\Models\BaseModel;
use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImageModel extends BaseModel
{
	use ProductImageField;

    protected $table = 'product_image';
    protected $primaryKey = 'product_image_id';

    public string $defaultSorting = 'sort_order';

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id', 'product_id');
    }
}
