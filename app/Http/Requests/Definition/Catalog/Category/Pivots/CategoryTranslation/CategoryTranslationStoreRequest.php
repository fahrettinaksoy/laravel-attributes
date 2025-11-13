<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category\Pivots\CategoryTranslation;

use App\Http\Requests\BaseStoreRequest;

class CategoryTranslationStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_translation_id' => ['nullable'],
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
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'category_translation_id.nullable' => 'Category Translation Id alanı için nullable kuralı geçersizdir.',
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
        ]);
    }
}