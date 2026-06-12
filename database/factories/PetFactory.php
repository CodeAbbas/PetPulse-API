<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pet>
 */
final class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition(): array
    {
        return [
            'owner_user_id' => User::factory(),
            'name' => $this->faker->firstName(),
            'species' => 'dog',
            'breed' => 'Labrador Retriever',
            'sex' => 'female',
            'date_of_birth' => $this->faker->dateTimeBetween('-10 years', '-1 year')->format('Y-m-d'),
        ];
    }
}