<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductTranslation;

use App\Http\Requests\BaseUpdateRequest;

class ProductTranslationUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
            'slug' => ['required', 'string', 'max:500'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keyword' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.string' => 'Name alanı için string kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.string' => 'Summary alanı için string kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.string' => 'Description alanı için string kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'slug.required' => 'Slug alanı için required kuralı geçersizdir.',
            'slug.string' => 'Slug alanı için string kuralı geçersizdir.',
            'slug.max' => 'Slug alanı için max kuralı geçersizdir.',
            'meta_title.nullable' => 'Meta Title alanı için nullable kuralı geçersizdir.',
            'meta_title.string' => 'Meta Title alanı için string kuralı geçersizdir.',
            'meta_title.max' => 'Meta Title alanı için max kuralı geçersizdir.',
            'meta_description.nullable' => 'Meta Description alanı için nullable kuralı geçersizdir.',
            'meta_description.string' => 'Meta Description alanı için string kuralı geçersizdir.',
            'meta_description.max' => 'Meta Description alanı için max kuralı geçersizdir.',
            'meta_keyword.nullable' => 'Meta Keyword alanı için nullable kuralı geçersizdir.',
            'meta_keyword.string' => 'Meta Keyword alanı için string kuralı geçersizdir.',
            'meta_keyword.max' => 'Meta Keyword alanı için max kuralı geçersizdir.',
        ]);
    }
}
