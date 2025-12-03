<?php

declare(strict_types=1);

namespace Database\Factories\Catalog\Review;

use App\Models\Catalog\Review\ReviewModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReviewModel>
 */
class ReviewModelFactory extends Factory
{
    protected $model = ReviewModel::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'code' => $this->faker->unique()->bothify("CODE-####"),
            'product_id' => $this->faker->numberBetween(1, 100),
            'account_id' => $this->faker->numberBetween(0, 1000),
            'author' => $this->faker->name(),
            'content' => $this->faker->realText(200),
            'rating' => $this->faker->numberBetween(1, 5),
            'status' => $this->faker->boolean(),
        ];
    }
}
