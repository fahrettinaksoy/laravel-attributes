<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Product;

use App\Models\Catalog\Product\ProductModel;
use Illuminate\Database\Seeder;

class ProductModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ProductModel::query()->delete();

        ProductModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
