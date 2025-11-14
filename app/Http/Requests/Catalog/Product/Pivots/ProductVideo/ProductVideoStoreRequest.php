<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivots\ProductVideo;

use App\Http\Requests\BaseStoreRequest;

class ProductVideoStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'source' => ['required', 'max:255'],
            'content' => ['required', 'max:255'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'source.required' => 'Source alanı için required kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
        ]);
    }
}