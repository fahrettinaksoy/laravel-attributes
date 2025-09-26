<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Language;

use App\Http\Requests\BaseStoreRequest;
use Illuminate\Validation\Rule;

class LanguageStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'name' => ['required', 'string', 'max:255'],
            'locale' => ['nullable', 'string', 'max:10'],
            'flag' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'name.required' => 'The language name field is required.',
            'name.string' => 'The language name must be a string.',
            'name.max' => 'The language name may not be greater than 255 characters.',
            'locale.string' => 'The locale must be a string.',
            'locale.max' => 'The locale may not be greater than 10 characters.',
            'flag.string' => 'The flag must be a string.',
            'flag.max' => 'The flag may not be greater than 255 characters.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'sort_order.min' => 'The sort order must be at least 0.',
            'status.boolean' => 'The status must be true or false.',
        ]);
    }
}
