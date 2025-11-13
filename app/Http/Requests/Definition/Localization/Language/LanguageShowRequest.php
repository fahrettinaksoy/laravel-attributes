<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseShowRequest;

class LanguageShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'language_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['nullable'],
            'name' => ['nullable'],
            'description' => ['nullable'],
            'image_path' => ['nullable'],
            'price' => ['nullable'],
            'currency_code' => ['nullable'],
            'stock' => ['nullable'],
            'sku' => ['nullable'],
            'category_id' => ['nullable'],
            'is_active' => ['nullable'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_by' => ['nullable'],
            'updated_at' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'language_id.nullable' => 'Language Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.nullable' => 'Code alanı için nullable kuralı geçersizdir.',
            'name.nullable' => 'Name alanı için nullable kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'image_path.nullable' => 'Image Path alanı için nullable kuralı geçersizdir.',
            'price.nullable' => 'Price alanı için nullable kuralı geçersizdir.',
            'currency_code.nullable' => 'Currency Code alanı için nullable kuralı geçersizdir.',
            'stock.nullable' => 'Stock alanı için nullable kuralı geçersizdir.',
            'sku.nullable' => 'Sku alanı için nullable kuralı geçersizdir.',
            'category_id.nullable' => 'Category Id alanı için nullable kuralı geçersizdir.',
            'is_active.nullable' => 'Is Active alanı için nullable kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
        ]);
    }
}