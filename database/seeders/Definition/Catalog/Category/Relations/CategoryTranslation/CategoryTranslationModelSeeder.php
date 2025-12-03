<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Catalog\Category\Relations\CategoryTranslation;

use App\Models\Definition\Catalog\Category\Relations\CategoryTranslation\CategoryTranslationModel;
use Illuminate\Database\Seeder;

class CategoryTranslationModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        CategoryTranslationModel::query()->delete();

        // Parent modelden kayıtları al
        $parents = \App\Models\Definition\Catalog\Category\CategoryModel::all();

        // Her parent için 1-5 arası ilişkili kayıt oluştur
        foreach ($parents as $parent) {
            CategoryTranslationModel::factory()
                ->count(rand(1, 5))
                ->create(['category_id' => $parent->category_id]);
        }
    }
}
