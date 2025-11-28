<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Http\Requests\BaseIndexRequest;

class ProductVideoTranslationIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_video_translation_id' => ['nullable'],
            'product_video_id' => ['required', 'exists:cat_product_video,product_video_id'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'name' => ['nullable', 'max:255'],
            'summary' => ['nullable', 'max:500'],
            'description' => ['nullable', 'max:500'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_video_translation_id.nullable' => 'Product Video Translation Id alanı için nullable kuralı geçersizdir.',
            'product_video_id.required' => 'Product Video Id alanı için required kuralı geçersizdir.',
            'product_video_id.exists' => 'Product Video Id alanı için exists kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'name.nullable' => 'Name alanı için nullable kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}