<?php

namespace Database\Factories\Catalog\Product\Pivot\ProductImage;

use App\Models\Catalog\Product\Pivot\ProductImage\ProductImageModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductImageModelFactory extends Factory
{
    protected $model = ProductImageModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'image_path' => 'products/' . $this->faker->uuid() . '.jpg',
            'alt_text' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
