<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Review;

use App\Http\Requests\BaseDestroyRequest;

class ReviewDestroyRequest extends BaseDestroyRequest
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
