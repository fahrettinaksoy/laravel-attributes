<?php

namespace Database\Seeders;

use Database\Seeders\Catalog\Product\ProductModelSeeder;
use Database\Seeders\Catalog\Product\Relations\ProductImage\ProductImageModelSeeder;
use Database\Seeders\Catalog\Product\Relations\ProductImage\Relations\ProductImageTranslation\ProductImageTranslationModelSeeder;
use Database\Seeders\Catalog\Product\Relations\ProductTranslation\ProductTranslationModelSeeder;
use Database\Seeders\Catalog\Product\Relations\ProductVideo\ProductVideoModelSeeder;
use Database\Seeders\Catalog\Product\Relations\ProductVideo\Relations\ProductVideoTranslation\ProductVideoTranslationModelSeeder;
use Database\Seeders\Catalog\Review\ReviewModelSeeder;
use Database\Seeders\Definition\Catalog\Brand\BrandModelSeeder;
use Database\Seeders\Definition\Catalog\Brand\Relations\BrandTranslation\BrandTranslationModelSeeder;
use Database\Seeders\Definition\Catalog\Category\CategoryModelSeeder;
use Database\Seeders\Definition\Catalog\Category\Relations\CategoryTranslation\CategoryTranslationModelSeeder;
use Database\Seeders\Definition\Localization\Currency\CurrencyModelSeeder;
use Database\Seeders\Definition\Localization\Language\LanguageModelSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // Tüm tabloları temizle
            $this->command->info('Truncating tables...');
            DB::table('cat_product')->truncate();
            // Diğer tablolar da eklenebilir

            $this->call(ProductModelSeeder::class);
            $this->call(ProductImageModelSeeder::class);
            $this->call(ProductImageTranslationModelSeeder::class);
            $this->call(ProductTranslationModelSeeder::class);
            $this->call(ProductVideoModelSeeder::class);
            $this->call(ProductVideoTranslationModelSeeder::class);
            $this->call(ReviewModelSeeder::class);
            $this->call(BrandModelSeeder::class);
            $this->call(BrandTranslationModelSeeder::class);
            $this->call(CategoryModelSeeder::class);
            $this->call(CategoryTranslationModelSeeder::class);
            $this->call(CurrencyModelSeeder::class);
            $this->call(LanguageModelSeeder::class);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
