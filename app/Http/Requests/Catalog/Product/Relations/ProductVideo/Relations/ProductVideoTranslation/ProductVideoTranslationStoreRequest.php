<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Http\Requests\BaseStoreRequest;

class ProductVideoTranslationStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_video_id' => ['required', 'exists:cat_product_video,product_video_id'],
            'code' => ['required', 'max:64'],
            'name' => ['nullable', 'max:255'],
            'summary' => ['nullable', 'max:500'],
            'description' => ['nullable', 'max:500'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_video_id.required' => 'Product Video Id alanı için required kuralı geçersizdir.',
            'product_video_id.exists' => 'Product Video Id alanı için exists kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'name.nullable' => 'Name alanı için nullable kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'summary.nullable' => 'Summary alanı için nullable kuralı geçersizdir.',
            'summary.max' => 'Summary alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
        ]);
    }
}