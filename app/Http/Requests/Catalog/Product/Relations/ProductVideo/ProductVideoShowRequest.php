<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo;

use App\Http\Requests\BaseShowRequest;

class ProductVideoShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_video_id' => ['nullable'],
            'product_id' => ['required', 'exists:cat_product,product_id'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'source' => ['required', 'max:255'],
            'content' => ['required', 'max:500'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_video_id.nullable' => 'Product Video Id alanı için nullable kuralı geçersizdir.',
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'source.required' => 'Source alanı için required kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}