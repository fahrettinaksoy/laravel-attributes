<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Http\Requests\BaseDestroyRequest;

class ProductVideoTranslationDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_video_translation_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_video_translation_id.nullable' => 'Product Video Translation Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}