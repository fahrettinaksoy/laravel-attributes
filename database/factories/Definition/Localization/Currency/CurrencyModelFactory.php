<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Localization\Currency;

use App\Models\Definition\Localization\Currency\CurrencyModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CurrencyModel>
 */
class CurrencyModelFactory extends Factory
{
    protected $model = CurrencyModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify('CODE-####'),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->realText(200),
            'image_path' => $this->faker->numberBetween(18, 80),
            'symbol_left' => $this->faker->word(),
            'symbol_right' => $this->faker->word(),
            'decimal_place' => $this->faker->word(),
            'decimal_point' => $this->faker->word(),
            'thousand_point' => $this->faker->word(),
            'value' => $this->faker->word(),
            'source' => $this->faker->word(),
            'last_synced_at' => $this->faker->word(),
            'is_crypto' => $this->faker->boolean(),
            'status' => $this->faker->boolean(),
        ];
    }
}
