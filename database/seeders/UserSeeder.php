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
            'name' => 'user1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        User::create([
            'name' => 'user2',
            'email' => 'user2@example.com',
            'password' => Hash::make('password'),
            'admin_status' => false,
        ]);

        User::create([
            'name' => 'user3',
            'email' => 'user3@example.com',
            'password' => Hash::make('password'),
            'admin_status' => true,
        ]);
    }
}
