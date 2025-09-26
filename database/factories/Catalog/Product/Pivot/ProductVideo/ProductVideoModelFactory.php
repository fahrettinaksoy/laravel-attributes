<?php

namespace Database\Factories\Catalog\Product\Pivot\ProductVideo;

use App\Models\Catalog\Product\Pivot\ProductVideo\ProductVideoModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVideoModelFactory extends Factory
{
    protected $model = ProductVideoModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'video_url' => $this->faker->url(),
            'title' => $this->faker->sentence(),
            'sort_order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
