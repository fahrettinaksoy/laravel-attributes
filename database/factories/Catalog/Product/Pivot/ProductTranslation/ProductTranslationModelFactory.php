<?php

namespace Database\Factories\Catalog\Product\Pivot\ProductTranslation;

use App\Models\Catalog\Product\Pivot\ProductTranslation\ProductTranslationModel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductTranslationModelFactory extends Factory
{
    protected $model = ProductTranslationModel::class;

    public function definition(): array
    {
        return [
            'product_id' => $this->faker->numberBetween(1, 100),
            'language_code' => 'EN',
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->paragraphs(2, true),
            'meta_title' => $this->faker->sentence(),
            'meta_description' => $this->faker->sentence(),
            'meta_keywords' => $this->faker->words(5, true),
        ];
    }
}
