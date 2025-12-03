<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation;

use App\Models\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation\ProductImageTranslationModel;
use Illuminate\Database\Seeder;

class ProductImageTranslationModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ProductImageTranslationModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Catalog\Product\Relations\ProductImage\ProductImageModel::all();

        // Her parent için 1-3 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            ProductImageTranslationModel::factory()
                ->count(rand(1, 3))
                ->create(['product_image_id' => $parent->product_image_id]);
        }
    }
}
