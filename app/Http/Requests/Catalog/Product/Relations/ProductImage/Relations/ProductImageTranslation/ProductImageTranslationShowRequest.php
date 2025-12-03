<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation;

use App\Http\Requests\BaseShowRequest;

class ProductImageTranslationShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), []);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), []);
    }
}
