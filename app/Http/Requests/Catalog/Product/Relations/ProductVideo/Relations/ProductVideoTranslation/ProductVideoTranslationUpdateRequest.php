<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Http\Requests\BaseUpdateRequest;

class ProductVideoTranslationUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.string' => 'Name alanı için string kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.string' => 'Summary alanı için string kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.string' => 'Description alanı için string kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
        ]);
    }
}
