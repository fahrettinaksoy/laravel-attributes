<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseDestroyRequest;

class LanguageDestroyRequest extends BaseDestroyRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'language_id' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'language_id.nullable' => 'Language Id alanı için nullable kuralı geçersizdir.',
        ]);
    }
}