<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Catalog\Category;

use App\Models\Definition\Catalog\Category\CategoryModel;
use Illuminate\Database\Seeder;

class CategoryModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        CategoryModel::query()->delete();

        CategoryModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
