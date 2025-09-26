<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product;

use App\Http\Requests\BaseUpdateRequest;
use Illuminate\Validation\Rule;

class ProductUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        $productId = $this->route('id');

        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'max:3'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('product', 'sku')->ignore($productId, 'product_id')],
            'category_id' => ['nullable', 'integer', 'exists:category,category_id'],
            'status' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'The product name field is required.',
            'name.string' => 'The product name must be a string.',
            'name.max' => 'The product name may not be greater than 255 characters.',
            'description.string' => 'The description must be a string.',
            'image.string' => 'The image path must be a string.',
            'image.max' => 'The image path may not be greater than 255 characters.',
            'price.required' => 'The product price field is required.',
            'price.numeric' => 'The product price must be a number.',
            'price.min' => 'The product price must be at least 0.01.',
            'currency.required' => 'The currency code field is required.',
            'currency.string' => 'The currency code must be a string.',
            'currency.max' => 'The currency code may not be greater than 3 characters.',
            'stock.integer' => 'The stock quantity must be an integer.',
            'stock.min' => 'The stock quantity must be at least 0.',
            'sku.string' => 'The SKU must be a string.',
            'sku.max' => 'The SKU may not be greater than 100 characters.',
            'sku.unique' => 'The SKU has already been taken.',
            'category_id.integer' => 'The category ID must be an integer.',
            'category_id.exists' => 'The selected category ID is invalid.',
            'status.boolean' => 'The status must be true or false.',
        ]);
    }
}
