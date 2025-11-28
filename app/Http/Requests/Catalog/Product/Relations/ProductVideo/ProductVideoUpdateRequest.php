<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo;

use App\Http\Requests\BaseUpdateRequest;

class ProductVideoUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'exists:cat_product,product_id'],
            'code' => ['required', 'max:64'],
            'source' => ['required', 'max:255'],
            'content' => ['required', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'source.required' => 'Source alanı için required kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
        ]);
    }
}