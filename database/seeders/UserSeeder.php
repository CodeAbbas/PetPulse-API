<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => (string) Str::uuid(),
                'name' => 'Sarah Mitchell',
                'email' => 'sarah@example.com',
                'password' => Hash::make('password123'),
                'role' => 'owner',
                'latitude' => 51.5074,
                'longitude' => -0.1278,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Dr. Alex Vance',
                'email' => 'alex@example.com',
                'password' => Hash::make('password123'),
                'role' => 'vet',
                'latitude' => 51.5150,
                'longitude' => -0.1420,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'System Admin',
                'email' => 'admin@petpulse.ac.uk',
                'password' => Hash::make('secure_admin_pass'),
                'role' => 'admin',
                'latitude' => null,
                'longitude' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}