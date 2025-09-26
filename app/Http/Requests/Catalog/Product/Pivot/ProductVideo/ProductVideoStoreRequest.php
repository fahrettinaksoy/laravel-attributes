<?php

declare(strict_types=1);

namespace App\Http\Requests\Catalog\Product\Pivot\ProductVideo;

use App\Http\Requests\BaseStoreRequest;
use Illuminate\Validation\Rule;

class ProductVideoStoreRequest extends BaseStoreRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'integer', 'exists:product,product_id'],
            'video_url' => ['required', 'string', 'max:255', 'url'],
            'title' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'product_id.required' => 'The product ID field is required.',
            'product_id.integer' => 'The product ID must be an integer.',
            'product_id.exists' => 'The selected product ID is invalid.',
            'video_url.required' => 'The video URL field is required.',
            'video_url.string' => 'The video URL must be a string.',
            'video_url.max' => 'The video URL may not be greater than 255 characters.',
            'video_url.url' => 'The video URL must be a valid URL format.',
            'title.string' => 'The video title must be a string.',
            'title.max' => 'The video title may not be greater than 255 characters.',
            'sort_order.integer' => 'The sort order must be an integer.',
            'sort_order.min' => 'The sort order must be at least 0.',
        ]);
    }
}
