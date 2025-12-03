<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Catalog\Brand\Relations\BrandTranslation;

use App\Models\Definition\Catalog\Brand\Relations\BrandTranslation\BrandTranslationModel;
use Illuminate\Database\Seeder;

class BrandTranslationModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        BrandTranslationModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Definition\Catalog\Brand\BrandModel::all();

        // Her parent için 1-5 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            BrandTranslationModel::factory()
                ->count(rand(1, 5))
                ->create(['brand_id' => $parent->brand_id]);
        }
    }
}
