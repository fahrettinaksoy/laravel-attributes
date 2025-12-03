<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Relations\ProductVideo;

use App\Http\Requests\BaseIndexRequest;

class ProductVideoIndexRequest extends BaseIndexRequest
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
