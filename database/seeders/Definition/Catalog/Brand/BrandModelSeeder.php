<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Catalog\Brand;

use App\Models\Definition\Catalog\Brand\BrandModel;
use Illuminate\Database\Seeder;

class BrandModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        BrandModel::query()->delete();

        BrandModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
