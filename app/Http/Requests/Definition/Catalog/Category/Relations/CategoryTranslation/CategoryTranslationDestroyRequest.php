<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category\Relations\CategoryTranslation;

use App\Http\Requests\BaseDestroyRequest;

class CategoryTranslationDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'category_translation_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'category_translation_id.nullable' => 'Category Translation Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}