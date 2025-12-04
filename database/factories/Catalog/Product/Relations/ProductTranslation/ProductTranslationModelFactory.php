<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product\Relations\ProductTranslation;

use App\Models\Catalog\Product\Relations\ProductTranslation\ProductTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductTranslationModel>
 */
class ProductTranslationModelFactory extends Factory
{
    protected $model = ProductTranslationModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'name' => $this->faker->words(3, true),
            'summary' => $this->faker->sentence(10),
            'description' => $this->faker->realText(200),
            'slug' => $this->faker->unique()->slug(),
            'meta_title' => $this->faker->sentence(4),
            'meta_description' => $this->faker->realText(200),
            'meta_keyword' => $this->faker->sentence(10),
        ];
    }
}
