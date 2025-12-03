<?php

declare(strict_types=1);

namespace Database\Seeders\Definition\Localization\Currency;

use App\Models\Definition\Localization\Currency\CurrencyModel;
use Illuminate\Database\Seeder;

class CurrencyModelSeeder extends Seeder
{
    public function run(): void
    {
        // Önce tabloyu temizle
        CurrencyModel::query()->delete();

        CurrencyModel::factory()
            ->count(rand(10, 50))
            ->create();
    }
}
