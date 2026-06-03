<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class PetSeeder extends Seeder
{
    public function run(): void
    {
        $ownerId = DB::table('users')->where('role', 'owner')->value('id');

        DB::table('pets')->insert([
            [
                'id' => (string) Str::uuid(),
                'owner_user_id' => $ownerId,
                'name' => 'Luna',
                'species' => 'dog',
                'breed' => 'Labrador Retriever',
                'sex' => 'female',
                'date_of_birth' => '2023-04-12',
                'microchip_number' => '956000012345678',
                'current_weight_kg' => 28.50,
                'current_bmi' => 22.10,
                'current_bmr_kcal' => 1450.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}