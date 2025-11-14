<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseUpdateRequest;

class LanguageUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'flag_path' => ['required', 'max:255'],
            'direction' => ['required', 'max:255'],
            'directory' => ['required', 'max:255'],
            'locale' => ['required', 'max:255'],
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
            'flag_path.required' => 'Flag Path alanı için required kuralı geçersizdir.',
            'flag_path.max' => 'Flag Path alanı için max kuralı geçersizdir.',
            'direction.required' => 'Direction alanı için required kuralı geçersizdir.',
            'direction.max' => 'Direction alanı için max kuralı geçersizdir.',
            'directory.required' => 'Directory alanı için required kuralı geçersizdir.',
            'directory.max' => 'Directory alanı için max kuralı geçersizdir.',
            'locale.required' => 'Locale alanı için required kuralı geçersizdir.',
            'locale.max' => 'Locale alanı için max kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
        ]);
    }
}