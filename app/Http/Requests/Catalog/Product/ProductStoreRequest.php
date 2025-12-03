<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product;

use App\Http\Requests\BaseStoreRequest;

class ProductStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['required', 'string', 'max:3', 'exists:def_loc_currency,code'],
            'sku' => ['required', 'string', 'max:50'],
            'category_id' => ['required', 'integer', 'exists:def_cat_category,category_id'],
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
            'price.nullable' => 'Price alanı için nullable kuralı geçersizdir.',
            'price.numeric' => 'Price alanı için numeric kuralı geçersizdir.',
            'price.min' => 'Price alanı için min kuralı geçersizdir.',
            'currency_code.required' => 'Currency Code alanı için required kuralı geçersizdir.',
            'currency_code.string' => 'Currency Code alanı için string kuralı geçersizdir.',
            'currency_code.max' => 'Currency Code alanı için max kuralı geçersizdir.',
            'currency_code.exists' => 'Currency Code alanı için exists kuralı geçersizdir.',
            'sku.required' => 'Sku alanı için required kuralı geçersizdir.',
            'sku.string' => 'Sku alanı için string kuralı geçersizdir.',
            'sku.max' => 'Sku alanı için max kuralı geçersizdir.',
            'category_id.required' => 'Category Id alanı için required kuralı geçersizdir.',
            'category_id.integer' => 'Category Id alanı için integer kuralı geçersizdir.',
            'category_id.exists' => 'Category Id alanı için exists kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'status.boolean' => 'Status alanı için boolean kuralı geçersizdir.',
        ]);
    }
}
