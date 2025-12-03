<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Catalog\Brand\Relations\BrandTranslation;

use App\Models\Definition\Catalog\Brand\Relations\BrandTranslation\BrandTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandTranslationModel>
 */
class BrandTranslationModelFactory extends Factory
{
    protected $model = BrandTranslationModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify("CODE-####"),
            'brand_id' => $this->faker->numberBetween(1, 100),
            'language_code' => $this->faker->bothify("CODE-####"),
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
