<?php

namespace Database\Seeders;

use Database\Seeders\Catalog\CategoryTableSeeder;
use Database\Seeders\Catalog\LanguageTableSeeder;
use Database\Seeders\Catalog\ProductTableSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            $this->call(LanguageTableSeeder::class);
            $this->call(CategoryTableSeeder::class);
            $this->call(ProductTableSeeder::class);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }
    }
}
