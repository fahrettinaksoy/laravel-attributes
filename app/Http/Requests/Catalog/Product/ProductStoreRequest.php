<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product;

use App\Http\Requests\BaseStoreRequest;

class ProductStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'image_path' => ['required', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency_code' => ['required', 'max:3', 'exists:def_loc_currency,code'],
            'sku' => ['required', 'max:50'],
            'category_id' => ['required', 'exists:def_cat_category,category_id'],
            'status' => ['required'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.required' => 'Image Path alanı için required kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'price.required' => 'Price alanı için required kuralı geçersizdir.',
            'price.numeric' => 'Price alanı için numeric kuralı geçersizdir.',
            'price.min' => 'Price alanı için min kuralı geçersizdir.',
            'currency_code.required' => 'Currency Code alanı için required kuralı geçersizdir.',
            'currency_code.max' => 'Currency Code alanı için max kuralı geçersizdir.',
            'currency_code.exists' => 'Currency Code alanı için exists kuralı geçersizdir.',
            'sku.required' => 'Sku alanı için required kuralı geçersizdir.',
            'sku.max' => 'Sku alanı için max kuralı geçersizdir.',
            'category_id.required' => 'Category Id alanı için required kuralı geçersizdir.',
            'category_id.exists' => 'Category Id alanı için exists kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
        ]);
    }
}