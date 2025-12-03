<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Currency;

use App\Http\Requests\BaseDestroyRequest;

class CurrencyDestroyRequest extends BaseDestroyRequest
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
