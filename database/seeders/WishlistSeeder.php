<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wishlist;
use App\Models\User;
use App\Models\Content;

class WishlistSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();
        $contents = Content::all();

        if ($users->isEmpty() || $contents->isEmpty()) {
            $this->command->info('No users or contents found. Skipping wishlist seeding.');
            return;
        }

        foreach ($users as $user) {
            // each user gets 3–5 wishlisted contents
            $wishlistedContents = $contents->random(rand(3, 5));

            foreach ($wishlistedContents as $content) {
                Wishlist::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'content_id' => $content->id,
                    ],
                    [
                        'isWished' => true,
                    ]
                );
            }
        }

        $this->command->info('Wishlists seeded successfully.');
    }
}
