<?php



namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Video;
use App\Models\User;
use App\Models\Content;

class VideoSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->toArray();
        $contents = Content::pluck('id')->toArray();

        if (empty($users) || empty($contents)) {
            $this->command->warn('Users or Contents table is empty. Seeder skipped.');
            return;
        }

        foreach (range(1, 10) as $i) {
            Video::create([
                'user_id' => $users[array_rand($users)],
                'content_id' => $contents[array_rand($contents)],
                'device_id' => 'DEVICE-' . rand(1000, 9999),
                'status' => collect(['not completed','completed'])->random(),
                'elapsed_time' => rand(0, 3600), // seconds
            ]);
        }

        $this->command->info('✅ VideoSeeder inserted 10 video records.');
    }
}
