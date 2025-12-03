<?php

declare(strict_types=1);

namespace Database\Seeders\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation;

use App\Models\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation\ProductVideoTranslationModel;
use Illuminate\Database\Seeder;

class ProductVideoTranslationModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        ProductVideoTranslationModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Catalog\Product\Relations\ProductVideo\ProductVideoModel::all();

        // Her parent için 1-3 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            ProductVideoTranslationModel::factory()
                ->count(rand(1, 3))
                ->create(['product_video_id' => $parent->product_video_id]);
        }
    }
}
