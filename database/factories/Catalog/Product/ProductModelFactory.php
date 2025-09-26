<?php

namespace Database\Factories\Catalog\Product;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'code' => strtoupper($this->faker->unique()->bothify('PRD-????-###')),
            'description' => $this->faker->paragraphs(2, true),
            'image' => $this->faker->boolean(80) ? 'products/' . $this->faker->slug() . '.jpg' : null,
            'price' => $this->faker->randomFloat(2, 9.99, 2999.99),
            'currency' => $this->faker->randomElement(['USD', 'EUR', 'TRY', 'GBP']),
            'stock' => $this->faker->numberBetween(0, 500),
            'sku' => $this->faker->boolean(90) ? strtoupper($this->faker->unique()->bothify('SKU-???-###')) : null,
            'category_id' => $this->faker->boolean(85) ? $this->faker->numberBetween(1, 15) : null,
            'status' => $this->faker->boolean(90),
        ];
    }
}
