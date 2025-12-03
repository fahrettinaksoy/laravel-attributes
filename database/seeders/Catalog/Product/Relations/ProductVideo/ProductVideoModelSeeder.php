<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Product\Relations\ProductVideo;

use App\Models\Catalog\Product\Relations\ProductVideo\ProductVideoModel;
use Illuminate\Database\Seeder;

class ProductVideoModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ProductVideoModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Catalog\Product\ProductModel::all();

        // Her parent için 1-5 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            ProductVideoModel::factory()
                ->count(rand(1, 5))
                ->create(['product_id' => $parent->product_id]);
        }
    }
}
