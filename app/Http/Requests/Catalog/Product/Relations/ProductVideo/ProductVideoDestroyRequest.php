<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo;

use App\Http\Requests\BaseDestroyRequest;

class ProductVideoDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_video_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_video_id.nullable' => 'Product Video Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}