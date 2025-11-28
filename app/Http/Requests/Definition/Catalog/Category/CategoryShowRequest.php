<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category;

use App\Http\Requests\BaseShowRequest;

class CategoryShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:500'],
            'image_path' => ['nullable', 'max:255'],
            'parent_id' => ['nullable', 'exists:def_cat_category,category_id'],
            'layout_id' => ['nullable'],
            'membership' => ['nullable'],
            'status' => ['required'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'category_id.nullable' => 'Category Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.nullable' => 'Image Path alanı için nullable kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'parent_id.nullable' => 'Parent Id alanı için nullable kuralı geçersizdir.',
            'parent_id.exists' => 'Parent Id alanı için exists kuralı geçersizdir.',
            'layout_id.nullable' => 'Layout Id alanı için nullable kuralı geçersizdir.',
            'membership.nullable' => 'Membership alanı için nullable kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}