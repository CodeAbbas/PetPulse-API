<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

final class UserSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::create([
            'name' => 'Sarah Mitchell',
            'email' => 'sarah.mitchell@example.com',
            'password' => 'password',
            'latitude' => 51.5074,
            'longitude' => -0.1278,
        ]);
        $owner->role = UserRole::Owner;
        $owner->save();

        $vet = User::create([
            'name' => 'Dr. Alex Vance',
            'email' => 'alex.vance@vetsnow.example',
            'password' => 'password',
        ]);
        $vet->role = UserRole::Vet;
        $vet->save();

        $admin = User::create([
            'name' => 'System Admin',
            'email' => 'admin@petpulse.local',
            'password' => 'password',
        ]);
        $admin->role = UserRole::Admin;
        $admin->save();
    }
}