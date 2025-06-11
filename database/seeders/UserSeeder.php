<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Password1945!'),
            'roles' => 'admin',
        ]);

        User::create([
            'email' => 'subscriber@gmail.com',
            'password' => Hash::make('Password1945!'),
            'roles' => 'subscriber',
        ]);

        
    }
}
