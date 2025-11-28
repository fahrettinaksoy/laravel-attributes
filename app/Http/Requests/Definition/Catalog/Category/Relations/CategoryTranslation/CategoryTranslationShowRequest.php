<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category\Relations\CategoryTranslation;

use App\Http\Requests\BaseShowRequest;

class CategoryTranslationShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_translation_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'language_code' => ['required', 'max:5', 'exists:def_loc_language,code'],
            'category_id' => ['required', 'exists:def_cat_category,category_id'],
            'name' => ['required', 'max:255'],
            'summary' => ['nullable', 'max:500'],
            'description' => ['nullable', 'max:500'],
            'slug' => ['required', 'max:255'],
            'meta_title' => ['nullable', 'max:255'],
            'meta_description' => ['nullable', 'max:500'],
            'meta_keyword' => ['nullable', 'max:500'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'category_translation_id.nullable' => 'Category Translation Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'language_code.required' => 'Language Code alanı için required kuralı geçersizdir.',
            'language_code.max' => 'Language Code alanı için max kuralı geçersizdir.',
            'language_code.exists' => 'Language Code alanı için exists kuralı geçersizdir.',
            'category_id.required' => 'Category Id alanı için required kuralı geçersizdir.',
            'category_id.exists' => 'Category Id alanı için exists kuralı geçersizdir.',
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
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}