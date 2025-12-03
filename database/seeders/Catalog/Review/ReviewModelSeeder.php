<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Review;

use App\Models\Catalog\Review\ReviewModel;
use Illuminate\Database\Seeder;

class ReviewModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ReviewModel::query()->delete();

        ReviewModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
