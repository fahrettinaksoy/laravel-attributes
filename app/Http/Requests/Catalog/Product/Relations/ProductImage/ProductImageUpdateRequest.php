<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage;

use App\Http\Requests\BaseUpdateRequest;

class ProductImageUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['nullable', 'exists:cat_product,product_id'],
            'code' => ['required', 'max:64'],
            'file_path' => ['required', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.nullable' => 'Product Id alanı için nullable kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'file_path.required' => 'File Path alanı için required kuralı geçersizdir.',
            'file_path.max' => 'File Path alanı için max kuralı geçersizdir.',
        ]);
    }
}