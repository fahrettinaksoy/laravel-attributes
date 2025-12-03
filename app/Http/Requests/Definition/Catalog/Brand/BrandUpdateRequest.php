<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Brand;

use App\Http\Requests\BaseUpdateRequest;

class BrandUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['required', 'integer', 'exists:def_cat_brand,brand_id'],
            'layout_id' => ['required', 'integer', 'exists:def_cat_layout,layout_id'],
            'membership' => ['required', 'integer'],
            'status' => ['required', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.string' => 'Name alanı için string kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.string' => 'Description alanı için string kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.nullable' => 'Image Path alanı için nullable kuralı geçersizdir.',
            'image_path.string' => 'Image Path alanı için string kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'parent_id.required' => 'Parent Id alanı için required kuralı geçersizdir.',
            'parent_id.integer' => 'Parent Id alanı için integer kuralı geçersizdir.',
            'parent_id.exists' => 'Parent Id alanı için exists kuralı geçersizdir.',
            'layout_id.required' => 'Layout Id alanı için required kuralı geçersizdir.',
            'layout_id.integer' => 'Layout Id alanı için integer kuralı geçersizdir.',
            'layout_id.exists' => 'Layout Id alanı için exists kuralı geçersizdir.',
            'membership.required' => 'Membership alanı için required kuralı geçersizdir.',
            'membership.integer' => 'Membership alanı için integer kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'status.boolean' => 'Status alanı için boolean kuralı geçersizdir.',
        ]);
    }
}
