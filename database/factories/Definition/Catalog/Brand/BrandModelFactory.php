<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Catalog\Brand;

use App\Models\Definition\Catalog\Brand\BrandModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BrandModel>
 */
class BrandModelFactory extends Factory
{
    protected $model = BrandModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify("CODE-####"),
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->realText(200),
            'image_path' => $this->faker->numberBetween(18, 80),
            'parent_id' => $this->faker->numberBetween(1, 100),
            'layout_id' => $this->faker->numberBetween(1, 100),
            'membership' => $this->faker->numberBetween(1, 9999),
            'status' => $this->faker->boolean(),
        ];
    }
}
