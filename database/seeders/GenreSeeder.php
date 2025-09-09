<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = [
            ['name' => 'Action', 'thumbnail' => 'action.jpg'],
            ['name' => 'Comedy', 'thumbnail' => 'comedy.jpg'],
            ['name' => 'Drama', 'thumbnail' => 'drama.jpg'],
            ['name' => 'Fantasy', 'thumbnail' => 'fantasy.jpg'],
            ['name' => 'Horror', 'thumbnail' => 'horror.jpg'],
            ['name' => 'Romance', 'thumbnail' => 'romance.jpg'],
            ['name' => 'Sci-Fi', 'thumbnail' => 'scifi.jpg'],
            ['name' => 'Thriller', 'thumbnail' => 'thriller.jpg'],
            ['name' => 'Documentary', 'thumbnail' => 'documentary.jpg'],
            ['name' => 'Animation', 'thumbnail' => 'animation.jpg'],
        ];

        foreach ($genres as $genre) {
            DB::table('genres')->insert([
                'name' => $genre['name'],
                'thumbnail' => $genre['thumbnail'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
