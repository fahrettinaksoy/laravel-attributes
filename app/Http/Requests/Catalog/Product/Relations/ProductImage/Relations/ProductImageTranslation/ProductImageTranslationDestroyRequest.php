<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation;

use App\Http\Requests\BaseDestroyRequest;

class ProductImageTranslationDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_image_translation_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_image_translation_id.nullable' => 'Product Image Translation Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}