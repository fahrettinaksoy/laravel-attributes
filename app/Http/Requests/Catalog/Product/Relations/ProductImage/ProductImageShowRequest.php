<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage;

use App\Http\Requests\BaseShowRequest;

class ProductImageShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_image_id' => ['nullable'],
            'product_id' => ['nullable', 'exists:cat_product,product_id'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'file_path' => ['required', 'max:255'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_image_id.nullable' => 'Product Image Id alanı için nullable kuralı geçersizdir.',
            'product_id.nullable' => 'Product Id alanı için nullable kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'file_path.required' => 'File Path alanı için required kuralı geçersizdir.',
            'file_path.max' => 'File Path alanı için max kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}