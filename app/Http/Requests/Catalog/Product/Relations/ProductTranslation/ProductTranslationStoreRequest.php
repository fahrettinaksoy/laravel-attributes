<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductTranslation;

use App\Http\Requests\BaseStoreRequest;

class ProductTranslationStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'exists:cat_product,product_id'],
            'code' => ['required', 'max:64'],
            'name' => ['required', 'max:255'],
            'summary' => ['nullable', 'max:500'],
            'description' => ['nullable', 'max:500'],
            'slug' => ['required', 'max:255'],
            'meta_title' => ['nullable', 'max:255'],
            'meta_description' => ['nullable', 'max:500'],
            'meta_keyword' => ['nullable', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'slug.required' => 'Slug alanı için required kuralı geçersizdir.',
            'slug.max' => 'Slug alanı için max kuralı geçersizdir.',
            'meta_title.nullable' => 'Meta Title alanı için nullable kuralı geçersizdir.',
            'meta_title.max' => 'Meta Title alanı için max kuralı geçersizdir.',
            'meta_description.nullable' => 'Meta Description alanı için nullable kuralı geçersizdir.',
            'meta_description.max' => 'Meta Description alanı için max kuralı geçersizdir.',
            'meta_keyword.nullable' => 'Meta Keyword alanı için nullable kuralı geçersizdir.',
            'meta_keyword.max' => 'Meta Keyword alanı için max kuralı geçersizdir.',
        ]);
    }
}