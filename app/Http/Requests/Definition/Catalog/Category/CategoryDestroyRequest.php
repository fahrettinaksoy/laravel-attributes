<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Category;

use App\Http\Requests\BaseDestroyRequest;

class CategoryDestroyRequest extends BaseDestroyRequest
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
