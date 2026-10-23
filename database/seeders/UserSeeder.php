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
            'name' => 'Jesus Anglas Ayme',
            'email' => 'jesus@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Augusto',
            'email' => 'augusto@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'vendedor',
            'email_verified_at' => now(),
        ]);
    }
}
