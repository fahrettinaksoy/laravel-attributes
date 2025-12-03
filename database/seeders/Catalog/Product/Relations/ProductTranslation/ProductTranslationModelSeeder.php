<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Product\Relations\ProductTranslation;

use App\Models\Catalog\Product\Relations\ProductTranslation\ProductTranslationModel;
use Illuminate\Database\Seeder;

class ProductTranslationModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ProductTranslationModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Catalog\Product\ProductModel::all();

        // Her parent için 1-5 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            ProductTranslationModel::factory()
                ->count(rand(1, 5))
                ->create(['product_id' => $parent->product_id]);
        }
    }
}
