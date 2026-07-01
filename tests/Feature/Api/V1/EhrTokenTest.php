<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\EhrToken;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Models\User;
use App\Services\EhrTokenService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class EhrTokenTest extends TestCase
{
    use RefreshDatabase;

    private function vet(): User
    {
        return User::factory()->create(['role' => 'vet']);
    }

    private function owner(): User
    {
        return User::factory()->create(['role' => 'owner']);
    }

    private function petFor(User $owner): Pet
    {
        return Pet::factory()->create(['owner_user_id' => $owner->id]);
    }

    // ── FR-05: issuance ──────────────────────────────────────────────────

    public function test_vet_can_issue_an_ehr_share_link(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());

        $response = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token", ['ttl_hours' => 24]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['share_url', 'token', 'expires_at', 'ttl_hours'],
            ]);

        // A row exists, storing only the HASH — never the raw token.
        $this->assertDatabaseCount('ehr_tokens', 1);
        $rawToken = $response->json('data.token');
        $this->assertDatabaseMissing('ehr_tokens', ['jwt_hash' => $rawToken]);
        $this->assertDatabaseHas('ehr_tokens', [
            'pet_id' => $pet->id,
            'issued_by_user_id' => $vet->id,
        ]);
    }

    public function test_owner_cannot_issue_a_share_link(): void
    {
        $owner = $this->owner();
        $pet = $this->petFor($owner);

        $this->actingAs($owner)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->assertForbidden();

        $this->assertDatabaseCount('ehr_tokens', 0);
    }

    public function test_issuing_requires_authentication(): void
    {
        $pet = $this->petFor($this->owner());

        $this->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->assertUnauthorized();
    }

    // ── FR-06: public decode ─────────────────────────────────────────────

    public function test_public_can_view_ehr_with_valid_token(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());
        HealthRecord::factory()->create([
            'pet_id' => $pet->id,
            'summary' => 'Annual checkup',
        ]);

        $token = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->json('data.token');

        // No auth on this request — it's public.
        $response = $this->getJson("/api/v1/ehr/{$token}");

        $response->assertOk()
            ->assertJsonPath('data.pet.name', $pet->name)
            ->assertJsonStructure([
                'data' => [
                    'pet' => ['name', 'species', 'breed', 'metrics'],
                    'health_records',
                    'share' => ['issued_at', 'expires_at'],
                ],
            ]);
    }

    public function test_first_access_is_audit_logged(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());

        $token = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->json('data.token');

        $this->assertNull(EhrToken::first()->first_accessed_at);

        $this->getJson("/api/v1/ehr/{$token}")->assertOk();

        $record = EhrToken::first();
        $this->assertNotNull($record->first_accessed_at);
        $this->assertNotNull($record->accessed_by_ip);
    }

    public function test_expired_token_is_rejected(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());

        $token = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token", ['ttl_hours' => 1])
            ->json('data.token');

        // Jump past expiry.
        Carbon::setTestNow(Carbon::now()->addHours(2));

        $this->getJson("/api/v1/ehr/{$token}")->assertNotFound();

        Carbon::setTestNow();
    }

    public function test_tampered_token_is_rejected(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());

        $token = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->json('data.token');

        // Flip a character in the payload segment.
        $parts = explode('.', $token);
        $parts[1] = $parts[1] . 'x';
        $tampered = implode('.', $parts);

        $this->getJson("/api/v1/ehr/{$tampered}")->assertNotFound();
    }

    public function test_revoked_token_is_rejected(): void
    {
        $vet = $this->vet();
        $pet = $this->petFor($this->owner());

        $token = $this->actingAs($vet)
            ->postJson("/api/v1/pets/{$pet->id}/ehr-token")
            ->json('data.token');

        EhrToken::first()->update(['revoked_at' => Carbon::now()]);

        $this->getJson("/api/v1/ehr/{$token}")->assertNotFound();
    }

    public function test_garbage_token_is_rejected(): void
    {
        $this->getJson('/api/v1/ehr/not-a-real-token')->assertNotFound();
    }

    // ── Service unit checks ──────────────────────────────────────────────

    public function test_service_hash_is_deterministic_and_not_the_raw_token(): void
    {
        $service = app(EhrTokenService::class);
        $issued = $service->issue('some-pet-id');

        $hash = $service->hash($issued['jwt']);

        $this->assertSame($hash, $service->hash($issued['jwt']));
        $this->assertNotSame($issued['jwt'], $hash);
        $this->assertSame(64, strlen($hash)); // SHA-256 hex
    }

    public function test_service_verifies_its_own_token(): void
    {
        $service = app(EhrTokenService::class);
        $issued = $service->issue('pet-123');

        $claims = $service->verify($issued['jwt']);

        $this->assertNotNull($claims);
        $this->assertSame('pet-123', $claims['pet_id']);
        $this->assertSame('ehr_share', $claims['purpose']);
    }
}