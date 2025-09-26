<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BaseDestroyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['sometimes', 'required_without:id', 'array', 'min:1'],
            'ids.*' => ['integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required_without' => 'The ids field is required when no route ID is provided.',
            'ids.array' => 'The ids field must be an array.',
            'ids.min' => 'At least one ID must be provided.',
            'ids.*.integer' => 'Each ID must be an integer.',
            'ids.*.min' => 'Each ID must be greater than 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'ids' => 'identifiers',
            'ids.*' => 'identifier',
        ];
    }

    protected function prepareForValidation(): void
    {
        // If we have a route parameter 'id', add it to validation context
        if ($this->route('id')) {
            $this->merge(['id' => $this->route('id')]);
        }
    }
}
