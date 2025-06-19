<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Content;
use App\Models\Genre;
use Illuminate\Support\Str;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure genres exist (insert your fixed genres if none)
        if (Genre::count() === 0) {
            $this->call(GenreSeeder::class);
        }

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $publishStatus = $faker->randomElement(['public', 'private', 'schedule']);
            $schedule = $publishStatus === 'schedule'
                ? $faker->dateTimeBetween('+1 day', '+1 month')
                : now(); // null if not scheduled

            Content::create([
                'video1' => 'videos/' . Str::uuid() . '.mp4',
                'title' => $faker->sentence(6),
                'description' => $faker->paragraphs(3, true),
                'publish' => $publishStatus,
                'schedule' => $schedule,
                'genre_id' => Genre::inRandomOrder()->first()->id,
                'image' => 'images/' . Str::uuid() . '.jpg',
                'view_count' => $faker->numberBetween(0, 10000),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
