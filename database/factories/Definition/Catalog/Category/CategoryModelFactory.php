<?php

declare(strict_types=1);

namespace Database\Factories\Definition\Catalog\Category;

use App\Models\Definition\Catalog\Category\CategoryModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoryModel>
 */
class CategoryModelFactory extends Factory
{
    protected $model = CategoryModel::class;

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
