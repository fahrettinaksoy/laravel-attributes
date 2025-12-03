<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseStoreRequest;

class LanguageStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'flag_path' => ['required', 'string', 'max:500'],
            'direction' => ['required', 'string', 'max:10'],
            'directory' => ['required', 'string', 'max:100'],
            'locale' => ['required', 'string', 'max:50'],
            'sort_order' => ['required', 'numeric'],
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
            'flag_path.required' => 'Flag Path alanı için required kuralı geçersizdir.',
            'flag_path.string' => 'Flag Path alanı için string kuralı geçersizdir.',
            'flag_path.max' => 'Flag Path alanı için max kuralı geçersizdir.',
            'direction.required' => 'Direction alanı için required kuralı geçersizdir.',
            'direction.string' => 'Direction alanı için string kuralı geçersizdir.',
            'direction.max' => 'Direction alanı için max kuralı geçersizdir.',
            'directory.required' => 'Directory alanı için required kuralı geçersizdir.',
            'directory.string' => 'Directory alanı için string kuralı geçersizdir.',
            'directory.max' => 'Directory alanı için max kuralı geçersizdir.',
            'locale.required' => 'Locale alanı için required kuralı geçersizdir.',
            'locale.string' => 'Locale alanı için string kuralı geçersizdir.',
            'locale.max' => 'Locale alanı için max kuralı geçersizdir.',
            'sort_order.required' => 'Sort Order alanı için required kuralı geçersizdir.',
            'sort_order.numeric' => 'Sort Order alanı için numeric kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'status.boolean' => 'Status alanı için boolean kuralı geçersizdir.',
        ]);
    }
}
