<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Currency;

use App\Http\Requests\BaseIndexRequest;

class CurrencyIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'currency_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:10'],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:500'],
            'image_path' => ['nullable', 'max:255'],
            'symbol_left' => ['nullable', 'max:50'],
            'symbol_right' => ['nullable', 'max:50'],
            'decimal_place' => ['required', 'max:10'],
            'decimal_point' => ['required', 'max:5'],
            'thousand_point' => ['required', 'max:5'],
            'value' => ['required', 'max:50'],
            'source' => ['nullable', 'max:100'],
            'last_synced_at' => ['nullable', 'max:50'],
            'is_crypto' => ['nullable'],
            'status' => ['required'],
            'created_at' => ['nullable'],
            'created_by' => ['nullable'],
            'updated_at' => ['nullable'],
            'updated_by' => ['nullable'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'currency_id.nullable' => 'Currency Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'name.required' => 'Name alanı için required kuralı geçersizdir.',
            'name.max' => 'Name alanı için max kuralı geçersizdir.',
            'description.nullable' => 'Description alanı için nullable kuralı geçersizdir.',
            'description.max' => 'Description alanı için max kuralı geçersizdir.',
            'image_path.nullable' => 'Image Path alanı için nullable kuralı geçersizdir.',
            'image_path.max' => 'Image Path alanı için max kuralı geçersizdir.',
            'symbol_left.nullable' => 'Symbol Left alanı için nullable kuralı geçersizdir.',
            'symbol_left.max' => 'Symbol Left alanı için max kuralı geçersizdir.',
            'symbol_right.nullable' => 'Symbol Right alanı için nullable kuralı geçersizdir.',
            'symbol_right.max' => 'Symbol Right alanı için max kuralı geçersizdir.',
            'decimal_place.required' => 'Decimal Place alanı için required kuralı geçersizdir.',
            'decimal_place.max' => 'Decimal Place alanı için max kuralı geçersizdir.',
            'decimal_point.required' => 'Decimal Point alanı için required kuralı geçersizdir.',
            'decimal_point.max' => 'Decimal Point alanı için max kuralı geçersizdir.',
            'thousand_point.required' => 'Thousand Point alanı için required kuralı geçersizdir.',
            'thousand_point.max' => 'Thousand Point alanı için max kuralı geçersizdir.',
            'value.required' => 'Value alanı için required kuralı geçersizdir.',
            'value.max' => 'Value alanı için max kuralı geçersizdir.',
            'source.nullable' => 'Source alanı için nullable kuralı geçersizdir.',
            'source.max' => 'Source alanı için max kuralı geçersizdir.',
            'last_synced_at.nullable' => 'Last Synced At alanı için nullable kuralı geçersizdir.',
            'last_synced_at.max' => 'Last Synced At alanı için max kuralı geçersizdir.',
            'is_crypto.nullable' => 'Is Crypto alanı için nullable kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}