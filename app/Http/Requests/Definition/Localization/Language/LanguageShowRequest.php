<?php

declare(strict_types=1);

namespace App\Http\Requests\Definition\Localization\Language;

use App\Http\Requests\BaseShowRequest;

class LanguageShowRequest extends BaseShowRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'language_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['nullable'],
            'name' => ['required', 'max:255'],
            'description' => ['nullable', 'max:255'],
            'flag_path' => ['required', 'max:255'],
            'direction' => ['required', 'max:255'],
            'directory' => ['required', 'max:255'],
            'locale' => ['required', 'max:255'],
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
            'language_id.nullable' => 'Language Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.nullable' => 'Code alanı için nullable kuralı geçersizdir.',
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
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}