<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseIndexRequest;

class LanguageIndexRequest extends BaseIndexRequest
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
