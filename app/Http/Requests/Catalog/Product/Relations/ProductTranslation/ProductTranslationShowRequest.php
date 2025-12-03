<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductTranslation;

use App\Http\Requests\BaseShowRequest;

class ProductTranslationShowRequest extends BaseShowRequest
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
