<?php

namespace Database\Seeders\Catalog;

use App\Models\Catalog\Language\LanguageModel;
use Illuminate\Database\Seeder;

class LanguageTableSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            [
                'code' => 'EN',
                'name' => 'English',
                'locale' => 'en_US',
                'flag' => 'flags/us.png',
                'sort_order' => 1,
                'status' => true,
            ],
            [
                'code' => 'TR',
                'name' => 'Türkçe',
                'locale' => 'tr_TR',
                'flag' => 'flags/tr.png',
                'sort_order' => 2,
                'status' => true,
            ],
            [
                'code' => 'DE',
                'name' => 'Deutsch',
                'locale' => 'de_DE',
                'flag' => 'flags/de.png',
                'sort_order' => 3,
                'status' => true,
            ],
            [
                'code' => 'FR',
                'name' => 'Français',
                'locale' => 'fr_FR',
                'flag' => 'flags/fr.png',
                'sort_order' => 4,
                'status' => true,
            ],
            [
                'code' => 'ES',
                'name' => 'Español',
                'locale' => 'es_ES',
                'flag' => 'flags/es.png',
                'sort_order' => 5,
                'status' => true,
            ],
        ];

        foreach ($languages as $language) {
            $existingLanguage = LanguageModel::where('code', $language['code'])->first();

            if (! $existingLanguage) {
                LanguageModel::create($language);
            }
        }
    }
}
