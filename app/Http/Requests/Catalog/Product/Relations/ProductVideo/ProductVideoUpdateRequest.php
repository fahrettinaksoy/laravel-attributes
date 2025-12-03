<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo;

use App\Http\Requests\BaseUpdateRequest;

class ProductVideoUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'source' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'source.required' => 'Source alanı için required kuralı geçersizdir.',
            'source.string' => 'Source alanı için string kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.string' => 'Content alanı için string kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
        ]);
    }
}
