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
        // Ensure genres exist
        if (Genre::count() === 0) {
            $this->call(GenreSeeder::class);
        }

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $publishStatus = $faker->randomElement(['public', 'private', 'schedule']);
            $schedule = $publishStatus === 'schedule'
                ? $faker->dateTimeBetween('+1 day', '+1 month')
                : null;

            Content::create([
                's3_details_json' => json_encode([
                    'video_path' => 'videos/' . Str::uuid() . '.mp4',
                    'size' => $faker->numberBetween(1_000_000, 50_000_000), // 1MB–50MB
                    'duration' => $faker->randomFloat(2, 30, 3600), // 30s to 1hr
                    'format' => 'mp4',
                ]),
                'title' => $faker->sentence(6),
                'description' => $faker->paragraphs(3, true),
                'publish' => $publishStatus,
                'schedule' => $schedule,
                'genre_id' => Genre::inRandomOrder()->first()->id,
                'image' => 'images/' . Str::uuid() . '.jpg',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
