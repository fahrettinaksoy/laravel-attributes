<?php

namespace Database\Seeders\Catalog;

use App\Models\Catalog\Product\ProductModel;
use App\Models\Catalog\Product\Pivot\ProductImage\ProductImageModel;
use App\Models\Catalog\Product\Pivot\ProductTranslation\ProductTranslationModel;
use App\Models\Catalog\Product\Pivot\ProductVideo\ProductVideoModel;
use App\Models\Catalog\Language\LanguageModel;
use Illuminate\Database\Seeder;

class ProductTableSeeder extends Seeder
{
    public function run(): void
    {
        $languages = LanguageModel::where('status', true)->pluck('code')->toArray();

        if (empty($languages)) {
            $languages = ['EN', 'TR'];
        }

        ProductModel::factory(27)->create()->each(function ($product) use ($languages) {
            foreach ($languages as $languageCode) {
                ProductTranslationModel::factory()->create(['product_id' => $product->product_id, 'language_code' => $languageCode]);
            }

            ProductImageModel::factory(4)->create(['product_id' => $product->product_id]);
            ProductVideoModel::factory(2)->create(['product_id' => $product->product_id]);
        });
    }
}
