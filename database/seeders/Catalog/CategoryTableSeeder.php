<?php

namespace Database\Seeders\Catalog;

use App\Models\Catalog\Category\CategoryModel;
use Illuminate\Database\Seeder;

class CategoryTableSeeder extends Seeder
{
    public function run(): void
    {
        CategoryModel::factory(15)->create();
    }
}
