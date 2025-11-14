<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivots\ProductTranslation;

use App\Http\Requests\BaseIndexRequest;

class ProductTranslationIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_translation_id' => ['nullable'],
            'product_id' => ['required', 'exists:cat_product,product_id'],
            'uuid' => ['nullable'],
            'code' => ['nullable'],
            'name' => ['required', 'max:255'],
            'summary' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'slug' => ['required', 'max:255'],
            'meta_title' => ['required', 'max:255'],
            'meta_description' => ['required', 'max:255'],
            'meta_keyword' => ['required', 'max:255'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_translation_id.nullable' => 'Product Translation Id alanı için nullable kuralı geçersizdir.',
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.nullable' => 'Code alanı için nullable kuralı geçersizdir.',
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'slug.required' => 'Slug alanı için required kuralı geçersizdir.',
            'slug.max' => 'Slug alanı için max kuralı geçersizdir.',
            'meta_title.required' => 'Meta Title alanı için required kuralı geçersizdir.',
            'meta_title.max' => 'Meta Title alanı için max kuralı geçersizdir.',
            'meta_description.required' => 'Meta Description alanı için required kuralı geçersizdir.',
            'meta_description.max' => 'Meta Description alanı için max kuralı geçersizdir.',
            'meta_keyword.required' => 'Meta Keyword alanı için required kuralı geçersizdir.',
            'meta_keyword.max' => 'Meta Keyword alanı için max kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}