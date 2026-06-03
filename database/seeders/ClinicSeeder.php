<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        $vetUserId = DB::table('users')->where('role', 'vet')->value('id');

        DB::table('clinics')->insert([
            [
                'id' => (string) Str::uuid(),
                'vet_user_id' => $vetUserId,
                'name' => 'VetsNow Emergency London',
                'address_line_1' => '123 Emergency Lane',
                'address_line_2' => null,
                'city' => 'London',
                'postcode' => 'NW1 4RY',
                'country_code' => 'GB',
                'latitude' => 51.5322,
                'longitude' => -0.1410,
                'phone_e164' => '+447700900123',
                'is_emergency_24_7' => true,
                'rating' => 4.80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'vet_user_id' => null,
                'name' => 'Blue Cross Animal Hospital',
                'address_line_1' => '1-10 Shepard Drive',
                'address_line_2' => 'Victoria',
                'city' => 'London',
                'postcode' => 'SW1V 4QQ',
                'country_code' => 'GB',
                'latitude' => 51.4925,
                'longitude' => -0.1444,
                'phone_e164' => '+447700900124',
                'is_emergency_24_7' => true,
                'rating' => 4.60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'vet_user_id' => null,
                'name' => 'PawCare Urgent Clinic',
                'address_line_1' => '45 Care Road',
                'address_line_2' => null,
                'city' => 'London',
                'postcode' => 'E1 6AN',
                'country_code' => 'GB',
                'latitude' => 51.5185,
                'longitude' => -0.0760,
                'phone_e164' => '+447700900125',
                'is_emergency_24_7' => true,
                'rating' => 4.90,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}