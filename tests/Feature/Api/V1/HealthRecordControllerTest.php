<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class HealthRecordControllerTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $u = User::factory()->create();
        $u->role = UserRole::Owner;
        $u->save();

        return $u;
    }

    private function vet(): User
    {
        $u = User::factory()->create();
        $u->role = UserRole::Vet;
        $u->save();

        return $u;
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->role = UserRole::Admin;
        $u->save();

        return $u;
    }

    // ─── Server-side computation ────────────────────────────────

    #[Test]
    public function store_computes_bmi_and_bmr_server_side(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'weight',
            'weight_kg' => 30.0,
            'height_cm' => 60.0,
            'summary' => 'Routine weigh-in',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.computed_metrics.bmi', 83.33)
            ->assertJsonPath('data.computed_metrics.bmr_kcal', 897.3);

        $this->assertDatabaseHas('health_records', [
            'pet_id' => $pet->id,
            'bmi' => 83.33,
            'bmr_kcal' => 897.3,
        ]);
    }

    #[Test]
    public function store_ignores_client_supplied_bmi_and_bmr(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'weight',
            'weight_kg' => 30.0,
            'height_cm' => 60.0,
            'summary' => 'Injection attempt',
            'bmi' => 1.0,            // attacker-supplied — must be ignored
            'bmr_kcal' => 1.0,       // attacker-supplied — must be ignored
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.computed_metrics.bmi', 83.33)
            ->assertJsonPath('data.computed_metrics.bmr_kcal', 897.3);
    }

    #[Test]
    public function store_computes_bmr_when_height_is_absent(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $response = $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'weight',
            'weight_kg' => 10.0,
            'summary' => 'Weight only, no height',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.computed_metrics.bmi', null)        // no height → null BMI
            ->assertJsonPath('data.computed_metrics.bmr_kcal', 393.64); // BMR still computed
    }

    // ─── Validation ─────────────────────────────────────────────

    #[Test]
    public function store_rejects_weight_below_lower_bound(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'weight',
            'weight_kg' => 0.05,
            'summary' => 'Too light',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('weight_kg');
    }

    #[Test]
    public function store_rejects_a_record_for_a_nonexistent_pet(): void
    {
        $this->actingAs($this->owner(), 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => '00000000-0000-4000-8000-000000000000',
            'record_type' => 'weight',
            'weight_kg' => 30.0,
            'summary' => 'Ghost pet',
        ])->assertUnprocessable()->assertJsonValidationErrorFor('pet_id');
    }

    // ─── Authorisation: create ──────────────────────────────────

    #[Test]
    public function an_owner_can_create_a_record_for_their_own_pet(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'weight',
            'weight_kg' => 25.0,
            'summary' => 'Own pet',
        ])->assertCreated();
    }

    #[Test]
    public function an_owner_cannot_create_a_record_for_another_owners_pet(): void
    {
        $owner = $this->owner();
        $otherPet = Pet::factory()->create(['owner_user_id' => $this->owner()->id]);

        $this->actingAs($owner, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $otherPet->id,
            'record_type' => 'weight',
            'weight_kg' => 25.0,
            'summary' => 'Not my pet',
        ])->assertForbidden();
    }

    #[Test]
    public function a_vet_can_create_a_record_for_any_pet(): void
    {
        $pet = Pet::factory()->create(['owner_user_id' => $this->owner()->id]);

        $this->actingAs($this->vet(), 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'examination',
            'weight_kg' => 25.0,
            'summary' => 'Vet exam',
        ])->assertCreated();
    }

    #[Test]
    public function a_vet_created_record_traces_the_vet_as_recorder(): void
    {
        $vet = $this->vet();
        $pet = Pet::factory()->create(['owner_user_id' => $this->owner()->id]);

        $this->actingAs($vet, 'sanctum')->postJson('/api/v1/health-records', [
            'pet_id' => $pet->id,
            'record_type' => 'examination',
            'weight_kg' => 25.0,
            'summary' => 'Vet exam',
        ])->assertCreated();

        $this->assertDatabaseHas('health_records', [
            'pet_id' => $pet->id,
            'recorded_by_user_id' => $vet->id,
        ]);
    }

    // ─── Authorisation: view & scoping ──────────────────────────

    #[Test]
    public function an_owner_sees_only_records_for_their_own_pets(): void
    {
        $owner = $this->owner();
        $ownPet = Pet::factory()->create(['owner_user_id' => $owner->id]);
        $otherPet = Pet::factory()->create(['owner_user_id' => $this->owner()->id]);

        HealthRecord::factory()->create(['pet_id' => $ownPet->id]);
        HealthRecord::factory()->create(['pet_id' => $otherPet->id]);

        $response = $this->actingAs($owner, 'sanctum')->getJson('/api/v1/health-records');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }

    #[Test]
    public function a_vet_sees_all_records(): void
    {
        HealthRecord::factory()->create(['pet_id' => Pet::factory()->create()->id]);
        HealthRecord::factory()->create(['pet_id' => Pet::factory()->create()->id]);

        $response = $this->actingAs($this->vet(), 'sanctum')->getJson('/api/v1/health-records');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    #[Test]
    public function an_owner_cannot_view_a_record_for_another_owners_pet(): void
    {
        $owner = $this->owner();
        $otherPet = Pet::factory()->create(['owner_user_id' => $this->owner()->id]);
        $record = HealthRecord::factory()->create(['pet_id' => $otherPet->id]);

        $this->actingAs($owner, 'sanctum')
            ->getJson("/api/v1/health-records/{$record->id}")
            ->assertForbidden();
    }

    #[Test]
    public function a_vet_can_view_any_record(): void
    {
        $record = HealthRecord::factory()->create(['pet_id' => Pet::factory()->create()->id]);

        $this->actingAs($this->vet(), 'sanctum')
            ->getJson("/api/v1/health-records/{$record->id}")
            ->assertOk();
    }

    // ─── Authorisation: delete ──────────────────────────────────

    #[Test]
    public function an_admin_can_delete_a_record(): void
    {
        $record = HealthRecord::factory()->create(['pet_id' => Pet::factory()->create()->id]);

        $this->actingAs($this->admin(), 'sanctum')
            ->deleteJson("/api/v1/health-records/{$record->id}")
            ->assertOk();

        $this->assertDatabaseMissing('health_records', ['id' => $record->id]);
    }

    #[Test]
    public function a_vet_cannot_delete_a_record(): void
    {
        $record = HealthRecord::factory()->create(['pet_id' => Pet::factory()->create()->id]);

        $this->actingAs($this->vet(), 'sanctum')
            ->deleteJson("/api/v1/health-records/{$record->id}")
            ->assertForbidden();
    }

    #[Test]
    public function an_owner_cannot_delete_a_record(): void
    {
        $owner = $this->owner();
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);
        $record = HealthRecord::factory()->create(['pet_id' => $pet->id]);

        $this->actingAs($owner, 'sanctum')
            ->deleteJson("/api/v1/health-records/{$record->id}")
            ->assertForbidden();
    }

    #[Test]
    public function unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/v1/health-records')->assertUnauthorized();
        $this->postJson('/api/v1/health-records', [])->assertUnauthorized();
    }
}