<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product\Relations\ProductImage;

use App\Models\Catalog\Product\Relations\ProductImage\ProductImageModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImageModel>
 */
class ProductImageModelFactory extends Factory
{
    protected $model = ProductImageModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify("CODE-####"),
            'file_path' => $this->faker->word(),
        ];
    }
}
