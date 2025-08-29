<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LangsTableSeeder extends Seeder
{
    public function run()
    {
        $langs = [
            ['title' => 'Español',    'iso_code' => 'es', 'lenguage_code' => 'es', 'locate' => 'es-ES', 'iva' => 21, 'available' => 1],
            ['title' => 'English',    'iso_code' => 'en', 'lenguage_code' => 'en', 'locate' => 'en-US', 'iva' => 21, 'available' => 1],
            ['title' => 'Français',   'iso_code' => 'fr', 'lenguage_code' => 'fr', 'locate' => 'fr-FR', 'iva' => 20, 'available' => 1],
            ['title' => 'Português',  'iso_code' => 'pt', 'lenguage_code' => 'pt', 'locate' => 'pt-PT', 'iva' => 23, 'available' => 1],
            ['title' => 'Deutsch',    'iso_code' => 'de', 'lenguage_code' => 'de', 'locate' => 'de-DE', 'iva' => 19, 'available' => 1],
            ['title' => 'Italia',     'iso_code' => 'it', 'lenguage_code' => 'it', 'locate' => 'it-IT', 'iva' => 22, 'available' => 1],
        ];

        foreach ($langs as $lang) {
            DB::table('langs')->insert([
                'uid'            => (string) Str::uuid(),
                'title'          => $lang['title'],
                'iso_code'       => $lang['iso_code'],
                'lenguage_code'  => $lang['lenguage_code'],
                'locate'         => $lang['locate'],
                'iva'            => $lang['iva'],
                'available'      => $lang['available'],
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }
    }
}
