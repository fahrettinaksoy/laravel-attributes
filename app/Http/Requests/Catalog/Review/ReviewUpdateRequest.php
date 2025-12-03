<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Review;

use App\Http\Requests\BaseUpdateRequest;

class ReviewUpdateRequest extends BaseUpdateRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'integer', 'exists:cat_product,product_id'],
            'account_id' => ['nullable', 'integer', 'exists:acc_account,account_id'],
            'author' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:800'],
            'rating' => ['required', 'numeric', 'min:0', 'max:10'],
            'status' => ['required', 'boolean'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.integer' => 'Product Id alanı için integer kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'account_id.nullable' => 'Account Id alanı için nullable kuralı geçersizdir.',
            'account_id.integer' => 'Account Id alanı için integer kuralı geçersizdir.',
            'account_id.exists' => 'Account Id alanı için exists kuralı geçersizdir.',
            'author.required' => 'Author alanı için required kuralı geçersizdir.',
            'author.string' => 'Author alanı için string kuralı geçersizdir.',
            'author.max' => 'Author alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.string' => 'Content alanı için string kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
            'rating.required' => 'Rating alanı için required kuralı geçersizdir.',
            'rating.numeric' => 'Rating alanı için numeric kuralı geçersizdir.',
            'rating.min' => 'Rating alanı için min kuralı geçersizdir.',
            'rating.max' => 'Rating alanı için max kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'status.boolean' => 'Status alanı için boolean kuralı geçersizdir.',
        ]);
    }
}
