<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HealthRecord;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HealthRecord>
 */
final class HealthRecordFactory extends Factory
{
    protected $model = HealthRecord::class;

    public function definition(): array
    {
        return [
            'pet_id' => Pet::factory(),
            'recorded_by_user_id' => null,
            'record_type' => 'examination',
            'weight_kg' => $this->faker->randomFloat(2, 5, 40),
            'summary' => $this->faker->sentence(),
            'recorded_at' => now(),
        ];
    }
}