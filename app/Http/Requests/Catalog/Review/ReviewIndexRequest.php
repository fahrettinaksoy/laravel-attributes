<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Review;

use App\Http\Requests\BaseIndexRequest;

class ReviewIndexRequest extends BaseIndexRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'review_id' => ['nullable'],
            'uuid' => ['nullable'],
            'code' => ['required', 'max:64'],
            'product_id' => ['required', 'exists:cat_product,product_id'],
            'account_id' => ['nullable', 'exists:acc_account,account_id'],
            'author' => ['required', 'max:150'],
            'content' => ['required', 'max:800'],
            'rating' => ['required', 'numeric', 'min:0', 'max:10'],
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
            'review_id.nullable' => 'Review Id alanı için nullable kuralı geçersizdir.',
            'uuid.nullable' => 'Uuid alanı için nullable kuralı geçersizdir.',
            'code.required' => 'Code alanı için required kuralı geçersizdir.',
            'code.max' => 'Code alanı için max kuralı geçersizdir.',
            'product_id.required' => 'Product Id alanı için required kuralı geçersizdir.',
            'product_id.exists' => 'Product Id alanı için exists kuralı geçersizdir.',
            'account_id.nullable' => 'Account Id alanı için nullable kuralı geçersizdir.',
            'account_id.exists' => 'Account Id alanı için exists kuralı geçersizdir.',
            'author.required' => 'Author alanı için required kuralı geçersizdir.',
            'author.max' => 'Author alanı için max kuralı geçersizdir.',
            'content.required' => 'Content alanı için required kuralı geçersizdir.',
            'content.max' => 'Content alanı için max kuralı geçersizdir.',
            'rating.required' => 'Rating alanı için required kuralı geçersizdir.',
            'rating.numeric' => 'Rating alanı için numeric kuralı geçersizdir.',
            'rating.min' => 'Rating alanı için min kuralı geçersizdir.',
            'rating.max' => 'Rating alanı için max kuralı geçersizdir.',
            'status.required' => 'Status alanı için required kuralı geçersizdir.',
            'created_at.nullable' => 'Created At alanı için nullable kuralı geçersizdir.',
            'created_by.nullable' => 'Created By alanı için nullable kuralı geçersizdir.',
            'updated_at.nullable' => 'Updated At alanı için nullable kuralı geçersizdir.',
            'updated_by.nullable' => 'Updated By alanı için nullable kuralı geçersizdir.',
        ]);
    }
}