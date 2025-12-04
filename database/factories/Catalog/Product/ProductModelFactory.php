<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductModel>
 */
class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->realText(200),
            'image_path' => $this->faker->numberBetween(18, 80),
            'price' => $this->faker->randomFloat(2, 10, 9999),
            'currency_code' => $this->faker->bothify('CODE-####'),
            'stock' => $this->faker->numberBetween(0, 1000),
            'sku' => $this->faker->unique()->bothify('SKU-####-????'),
            'category_id' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->boolean(),
        ];
    }
}
