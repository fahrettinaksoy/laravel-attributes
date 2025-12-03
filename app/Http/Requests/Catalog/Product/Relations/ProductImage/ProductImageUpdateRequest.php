<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage;

use App\Http\Requests\BaseUpdateRequest;

class ProductImageUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'file_path' => ['required', 'string', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'file_path.required' => 'File Path alanı için required kuralı geçersizdir.',
            'file_path.string' => 'File Path alanı için string kuralı geçersizdir.',
            'file_path.max' => 'File Path alanı için max kuralı geçersizdir.',
        ]);
    }
}
