<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Models\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation\ProductVideoTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVideoTranslationModel>
 */
class ProductVideoTranslationModelFactory extends Factory
{
    protected $model = ProductVideoTranslationModel::class;

    public function definition(): array
    {
        return [
            'product_video_id' => $this->faker->numberBetween(1, 100),
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify("CODE-####"),
            'name' => $this->faker->words(3, true),
            'summary' => $this->faker->sentence(10),
            'description' => $this->faker->realText(200),
        ];
    }
}
