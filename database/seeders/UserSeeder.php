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
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'),
            'roles' => 'admin',
        ]);

        User::create([
            'first_name' => 'Subscriber',
            'email' => 'subscriber@gmail.com',
            'password' => Hash::make('password123'),
            'roles' => 'subscriber',
        ]);

        User::create([
            'first_name' => 'Subscriber',
            'email' => 'subscriber1@gmail.com',
            'password' => Hash::make('password123'),
            'roles' => 'subscriber',
        ]);

        User::create([
            'first_name' => 'Subscriber',
            'email' => 'subscriber2@gmail.com',
            'password' => Hash::make('password123'),
            'roles' => 'subscriber',
        ]);

        User::create([
            'first_name' => 'Subscriber',
            'email' => 'subscriber3@gmail.com',
            'password' => Hash::make('password123'),
            'roles' => 'subscriber',
        ]);
    }
}
