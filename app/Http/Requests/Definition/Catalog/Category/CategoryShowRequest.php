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
            'code' => ['nullable'],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'image_path' => ['required', 'max:255'],
            'parent_id' => ['required', 'max:5'],
            'layout_id' => ['required', 'max:5'],
            'membership' => ['required', 'max:5'],
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
            'code.nullable' => 'Code alanı için nullable kuralı geçersizdir.',
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.required' => 'Image Path alanı için required kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'parent_id.required' => 'Parent Id alanı için required kuralı geçersizdir.',
            'parent_id.max' => 'Parent Id alanı için max kuralı geçersizdir.',
            'layout_id.required' => 'Layout Id alanı için required kuralı geçersizdir.',
            'layout_id.max' => 'Layout Id alanı için max kuralı geçersizdir.',
            'membership.required' => 'Membership alanı için required kuralı geçersizdir.',
            'membership.max' => 'Membership alanı için max kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}