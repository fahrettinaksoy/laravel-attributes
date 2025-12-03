<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Catalog\Brand\Relations\BrandTranslation;

use App\Http\Requests\BaseIndexRequest;

class BrandTranslationIndexRequest extends BaseIndexRequest
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
