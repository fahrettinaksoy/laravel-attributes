<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Catalog\Category\Relations\CategoryTranslation;

use App\Models\Definition\Catalog\Category\Relations\CategoryTranslation\CategoryTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryTranslationModel>
 */
class CategoryTranslationModelFactory extends Factory
{
    protected $model = CategoryTranslationModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'category_id' => $this->faker->numberBetween(1, 100),
            'language_code' => $this->faker->bothify('CODE-####'),
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
