<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivots\ProductTranslation;

use App\Http\Requests\BaseStoreRequest;

class ProductTranslationStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'max:255'],
            'summary' => ['nullable', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'slug' => ['required', 'max:255'],
            'meta_title' => ['required', 'max:255'],
            'meta_description' => ['required', 'max:255'],
            'meta_keyword' => ['required', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
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
        ]);
    }
}