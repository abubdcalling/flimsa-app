<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Content;
use Illuminate\Support\Str;

class HistorySeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->all();
        $contents = Content::pluck('id')->all();

        if (empty($users) || empty($contents)) {
            $this->command->info('No users or contents found, skipping history seeding.');
            return;
        }

        $faker = \Faker\Factory::create();

        // Insert 100 random history records
        for ($i = 0; $i < 100; $i++) {
            DB::table('histories')->insert([
                'user_id' => $faker->randomElement($users),
                'content_id' => $faker->randomElement($contents),
                'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
                'updated_at' => now(),
            ]);
        }
    }
}
