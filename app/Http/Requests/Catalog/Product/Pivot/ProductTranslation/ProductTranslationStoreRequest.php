<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivot\ProductTranslation;

use App\Http\Requests\BaseStoreRequest;
use Illuminate\Validation\Rule;

class ProductTranslationStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'integer', 'exists:products,product_id'],
            'language_code' => ['required', 'string', 'max:5',
                Rule::unique('product_translations')->where(function ($query) {
                    return $query->where('product_id', $this->product_id);
                }),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string'],
            'meta_keywords' => ['nullable', 'string', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'The product ID field is required.',
            'product_id.integer' => 'The product ID must be an integer.',
            'product_id.exists' => 'The selected product ID is invalid.',
            'language_code.required' => 'The language code field is required.',
            'language_code.string' => 'The language code must be a string.',
            'language_code.max' => 'The language code may not be greater than 5 characters.',
            'language_code.unique' => 'A translation for this product and language already exists.',
            'name.required' => 'The product name field is required.',
            'name.string' => 'The product name must be a string.',
            'name.max' => 'The product name may not be greater than 255 characters.',
            'description.string' => 'The description must be a string.',
            'meta_title.string' => 'The meta title must be a string.',
            'meta_title.max' => 'The meta title may not be greater than 255 characters.',
            'meta_description.string' => 'The meta description must be a string.',
            'meta_keywords.string' => 'The meta keywords must be a string.',
            'meta_keywords.max' => 'The meta keywords may not be greater than 255 characters.',
        ]);
    }
}
