<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Currency;

use App\Http\Requests\BaseUpdateRequest;

class CurrencyUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'image_path' => ['required', 'max:255'],
            'symbol_left' => ['required', 'max:255'],
            'symbol_right' => ['required', 'max:255'],
            'decimal_place' => ['required', 'max:255'],
            'decimal_point' => ['required', 'max:255'],
            'thousand_point' => ['required', 'max:255'],
            'value' => ['required', 'max:255'],
            'last_synced_at' => ['nullable', 'max:255'],
            'is_crypto' => ['required'],
            'status' => ['required'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.required' => 'Image Path alanı için required kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'symbol_left.required' => 'Symbol Left alanı için required kuralı geçersizdir.',
            'symbol_left.max' => 'Symbol Left alanı için max kuralı geçersizdir.',
            'symbol_right.required' => 'Symbol Right alanı için required kuralı geçersizdir.',
            'symbol_right.max' => 'Symbol Right alanı için max kuralı geçersizdir.',
            'decimal_place.required' => 'Decimal Place alanı için required kuralı geçersizdir.',
            'decimal_place.max' => 'Decimal Place alanı için max kuralı geçersizdir.',
            'decimal_point.required' => 'Decimal Point alanı için required kuralı geçersizdir.',
            'decimal_point.max' => 'Decimal Point alanı için max kuralı geçersizdir.',
            'thousand_point.required' => 'Thousand Point alanı için required kuralı geçersizdir.',
            'thousand_point.max' => 'Thousand Point alanı için max kuralı geçersizdir.',
            'value.required' => 'Value alanı için required kuralı geçersizdir.',
            'value.max' => 'Value alanı için max kuralı geçersizdir.',
            'last_synced_at.nullable' => 'Last Synced At alanı için nullable kuralı geçersizdir.',
            'last_synced_at.max' => 'Last Synced At alanı için max kuralı geçersizdir.',
            'is_crypto.required' => 'Is Crypto alanı için required kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
        ]);
    }
}