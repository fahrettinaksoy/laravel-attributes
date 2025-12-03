<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Currency;

use App\Http\Requests\BaseUpdateRequest;

class CurrencyUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'symbol_left' => ['nullable', 'string', 'max:50'],
            'symbol_right' => ['nullable', 'string', 'max:50'],
            'decimal_place' => ['required', 'string', 'max:10'],
            'decimal_point' => ['required', 'string', 'max:5'],
            'thousand_point' => ['required', 'string', 'max:5'],
            'value' => ['required', 'string', 'max:50'],
            'source' => ['nullable', 'string', 'max:100'],
            'last_synced_at' => ['nullable', 'string', 'max:50'],
            'is_crypto' => ['required', 'boolean'],
            'status' => ['required', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.string' => 'Name alanı için string kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.string' => 'Description alanı için string kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.nullable' => 'Image Path alanı için nullable kuralı geçersizdir.',
            'image_path.string' => 'Image Path alanı için string kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'symbol_left.nullable' => 'Symbol Left alanı için nullable kuralı geçersizdir.',
            'symbol_left.string' => 'Symbol Left alanı için string kuralı geçersizdir.',
            'symbol_left.max' => 'Symbol Left alanı için max kuralı geçersizdir.',
            'symbol_right.nullable' => 'Symbol Right alanı için nullable kuralı geçersizdir.',
            'symbol_right.string' => 'Symbol Right alanı için string kuralı geçersizdir.',
            'symbol_right.max' => 'Symbol Right alanı için max kuralı geçersizdir.',
            'decimal_place.required' => 'Decimal Place alanı için required kuralı geçersizdir.',
            'decimal_place.string' => 'Decimal Place alanı için string kuralı geçersizdir.',
            'decimal_place.max' => 'Decimal Place alanı için max kuralı geçersizdir.',
            'decimal_point.required' => 'Decimal Point alanı için required kuralı geçersizdir.',
            'decimal_point.string' => 'Decimal Point alanı için string kuralı geçersizdir.',
            'decimal_point.max' => 'Decimal Point alanı için max kuralı geçersizdir.',
            'thousand_point.required' => 'Thousand Point alanı için required kuralı geçersizdir.',
            'thousand_point.string' => 'Thousand Point alanı için string kuralı geçersizdir.',
            'thousand_point.max' => 'Thousand Point alanı için max kuralı geçersizdir.',
            'value.required' => 'Value alanı için required kuralı geçersizdir.',
            'value.string' => 'Value alanı için string kuralı geçersizdir.',
            'value.max' => 'Value alanı için max kuralı geçersizdir.',
            'source.nullable' => 'Source alanı için nullable kuralı geçersizdir.',
            'source.string' => 'Source alanı için string kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'last_synced_at.nullable' => 'Last Synced At alanı için nullable kuralı geçersizdir.',
            'last_synced_at.string' => 'Last Synced At alanı için string kuralı geçersizdir.',
            'last_synced_at.max' => 'Last Synced At alanı için max kuralı geçersizdir.',
            'is_crypto.required' => 'Is Crypto alanı için required kuralı geçersizdir.',
            'is_crypto.boolean' => 'Is Crypto alanı için boolean kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'status.boolean' => 'Status alanı için boolean kuralı geçersizdir.',
        ]);
    }
}
