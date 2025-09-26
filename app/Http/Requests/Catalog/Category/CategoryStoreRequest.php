<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Category;

use App\Http\Requests\BaseStoreRequest;
use Illuminate\Validation\Rule;

class CategoryStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'min:0', 'exists:category,category_id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'The category name field is required.',
            'name.string' => 'The category name must be a string.',
            'name.max' => 'The category name may not be greater than 255 characters.',
            'description.string' => 'The description must be a string.',
            'image.string' => 'The image path must be a string.',
            'image.max' => 'The image path may not be greater than 255 characters.',
            'parent_id.integer' => 'The parent ID must be an integer.',
            'parent_id.min' => 'The parent ID must be at least 0.',
            'parent_id.exists' => 'The selected parent ID is invalid.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'sort_order.min' => 'The sort order must be at least 0.',
            'status.boolean' => 'The status must be true or false.',
        ]);
    }
}
