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
            'first_name' => 'Admin',
            'email' => 'admins@gmail.com',
            'password' => Hash::make('Password1945!'),
            'roles' => 'admin',
        ]);

        User::create([
            'first_name' => 'Subscriber',
            'email' => 'subscriber@gmail.com',
            'password' => Hash::make('Password1945!'),
            'roles' => 'subscriber',
        ]);

        
    }
}
