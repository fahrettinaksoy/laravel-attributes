<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Brand;

use App\Http\Requests\BaseShowRequest;

class BrandShowRequest extends BaseShowRequest
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
