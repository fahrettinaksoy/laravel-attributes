<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage;

use App\Http\Requests\BaseDestroyRequest;

class ProductImageDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_image_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_image_id.nullable' => 'Product Image Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}