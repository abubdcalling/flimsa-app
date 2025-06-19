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
        // Make sure at least one genre exists
        if (Genre::count() === 0) {
            Genre::create(['name' => 'General']);
        }

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            $publishStatus = $faker->randomElement(['public', 'private', 'schedule']);
            $schedule = $publishStatus === 'schedule'
                ? $faker->dateTimeBetween('+1 day', '+1 month')
                : now();

            Content::create([
                'video1' => 'videos/' . Str::uuid() . '.mp4',
                'title' => $faker->sentence(6),
                'description' => $faker->paragraphs(3, true),
                'publish' => $publishStatus,
                'schedule' => $schedule,
                'genre_id' => Genre::inRandomOrder()->first()->id,
                'image' => 'images/' . Str::uuid() . '.jpg',
                'created_at' => now(),
                'updated_at' => now(),
                'view_count' => $faker->numberBetween(0, 10000),
            ]);
        }
    }
}
