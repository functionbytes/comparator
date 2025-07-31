<?php

namespace Database\Seeders;

    use Illuminate\Database\Seeder;
    use Illuminate\Support\Facades\DB;
    use Illuminate\Support\Str;

class CategoriesTableSeeder extends Seeder
{
        public function run()
    {
        $categories = [
            ['id' => 3,  'title' => 'Golf',      'uid' => 'es', 'available' => 1],
            ['id' => 4,  'title' => 'Caza',      'uid' => 'en', 'available' => 1],
            ['id' => 5,  'title' => 'Pesca',     'uid' => 'fr', 'available' => 1],
            ['id' => 6,  'title' => 'Hipica',    'uid' => 'pt', 'available' => 1],
            ['id' => 7,  'title' => 'Buceo',     'uid' => 'de', 'available' => 1],
            ['id' => 8,  'title' => 'Nautica',   'uid' => 'it', 'available' => 1],
            ['id' => 9,  'title' => 'Esqui',     'uid' => 'it', 'available' => 1],
            ['id' => 10, 'title' => 'Padel',     'uid' => 'it', 'available' => 1],
            ['id' => 11, 'title' => 'Aventura',  'uid' => 'it', 'available' => 1],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'id'          => $category['id'],
                'uid'         => (string) Str::uuid(),
                'management_id'   => NULL,
                'title'       => $category['title'],
                'available'   => $category['available'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }
}

