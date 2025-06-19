<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Content;

class LikeSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->all();
        $contents = Content::pluck('id')->all();

        if (empty($users) || empty($contents)) {
            $this->command->info('No users or contents found. Skipping LikeSeeder.');
            return;
        }

        $faker = \Faker\Factory::create();

        for ($i = 0; $i < 100; $i++) {
            DB::table('likes')->insert([
                'user_id' => $faker->randomElement($users),
                'content_id' => $faker->randomElement($contents),
                'is_liked' => $faker->boolean(80),  // 80% chance liked
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
}
