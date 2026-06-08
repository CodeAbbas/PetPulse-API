<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Enums\PetSpecies;
use App\Models\BehavioralEvent;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PetTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_owner(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $this->assertTrue($pet->owner->is($owner));
    }

    #[Test]
    public function it_casts_species_to_enum(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $this->assertInstanceOf(PetSpecies::class, $pet->species);
        $this->assertSame(PetSpecies::Dog, $pet->species);
    }

    #[Test]
    public function soft_deleting_a_pet_preserves_health_records(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);
        HealthRecord::create([
            'pet_id' => $pet->id,
            'record_type' => 'examination',
            'summary' => 'Annual check',
            'recorded_at' => now(),
        ]);

        $pet->delete();

        $this->assertSoftDeleted($pet);
        $this->assertDatabaseCount('health_records', 1);
    }

    #[Test]
    public function deleting_an_owner_cascades_to_pets(): void
    {
        $owner = User::factory()->create();
        $pet = Pet::create([
            'owner_user_id' => $owner->id,
            'name' => 'Luna',
            'species' => 'dog',
        ]);

        $owner->delete();

        $this->assertDatabaseMissing('pets', ['id' => $pet->id]);
    }
}