<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category\Pivots\CategoryTranslation;

use App\Http\Requests\BaseUpdateRequest;

class CategoryTranslationUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'language_code' => ['required', 'max:3', 'exists:def_loc_currency,code'],
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
            'language_code.required' => 'Language Code alanı için required kuralı geçersizdir.',
            'language_code.max' => 'Language Code alanı için max kuralı geçersizdir.',
            'language_code.exists' => 'Language Code alanı için exists kuralı geçersizdir.',
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