<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Localization\Language;

use App\Models\Definition\Localization\Language\LanguageModel;
use Illuminate\Database\Seeder;

class LanguageModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        LanguageModel::query()->delete();

        LanguageModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
