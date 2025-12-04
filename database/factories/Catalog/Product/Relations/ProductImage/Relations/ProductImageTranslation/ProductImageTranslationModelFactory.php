<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation;

use App\Models\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation\ProductImageTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImageTranslationModel>
 */
class ProductImageTranslationModelFactory extends Factory
{
    protected $model = ProductImageTranslationModel::class;

    public function definition(): array
    {
        return [
            'product_image_id' => $this->faker->numberBetween(18, 80),
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'name' => $this->faker->words(3, true),
            'summary' => $this->faker->sentence(10),
            'description' => $this->faker->realText(200),
        ];
    }
}
