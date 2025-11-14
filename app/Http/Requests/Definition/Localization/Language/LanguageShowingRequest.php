<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use Illuminate\Foundation\Http\FormRequest;

class LanguageShowingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'sort_order' => ['required', 'max:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort_order.required' => 'Sort Order alanı için required kuralı geçersizdir.',
            'sort_order.max' => 'Sort Order alanı için max kuralı geçersizdir.',
        ];
    }
}