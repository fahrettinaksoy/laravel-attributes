<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category;

use App\Http\Requests\BaseDestroyRequest;

class CategoryDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'category_id.nullable' => 'Category Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}