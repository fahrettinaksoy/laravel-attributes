<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivot\ProductImage;;

use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Validation\Rule;

class ProductImageUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'image_path' => ['required', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'The product ID field is required.',
            'product_id.integer' => 'The product ID must be an integer.',
            'product_id.exists' => 'The selected product ID is invalid.',
            'image_path.required' => 'The image path field is required.',
            'image_path.string' => 'The image path must be a string.',
            'image_path.max' => 'The image path may not be greater than 255 characters.',
            'alt_text.string' => 'The alt text must be a string.',
            'alt_text.max' => 'The alt text may not be greater than 255 characters.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'sort_order.min' => 'The sort order must be at least 0.',
        ]);
    }
}
