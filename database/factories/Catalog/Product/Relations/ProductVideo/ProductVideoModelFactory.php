<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product\Relations\ProductVideo;

use App\Models\Catalog\Product\Relations\ProductVideo\ProductVideoModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVideoModel>
 */
class ProductVideoModelFactory extends Factory
{
    protected $model = ProductVideoModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'source' => $this->faker->word(),
            'content' => $this->faker->realText(200),
        ];
    }
}
