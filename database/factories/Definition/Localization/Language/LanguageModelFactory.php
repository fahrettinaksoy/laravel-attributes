<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Localization\Language;

use App\Models\Definition\Localization\Language\LanguageModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LanguageModel>
 */
class LanguageModelFactory extends Factory
{
    protected $model = LanguageModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->realText(200),
            'flag_path' => $this->faker->word(),
            'direction' => $this->faker->word(),
            'directory' => $this->faker->word(),
            'locale' => $this->faker->word(),
            'sort_order' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->boolean(),
        ];
    }
}
