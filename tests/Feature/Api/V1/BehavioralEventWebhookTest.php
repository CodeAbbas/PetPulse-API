<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Models\BehavioralEvent;
use App\Models\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BehavioralEventWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.webhook.secret' => self::SECRET]);
    }

    private function payload(Pet $pet): array
    {
        return [
            'event_id' => '11111111-1111-4111-8111-111111111111',
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
            'severity' => 'critical',
            'confidence_score' => 0.85,
            'recorded_at' => now()->toIso8601String(),
        ];
    }

    #[Test]
    public function a_valid_webhook_is_accepted(): void
    {
        $pet = Pet::factory()->create();

        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $this->payload($pet))
            ->assertCreated();

        $this->assertDatabaseHas('behavioral_events', [
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
        ]);
    }

    #[Test]
    public function a_webhook_without_the_secret_is_rejected(): void
    {
        $pet = Pet::factory()->create();

        $this->postJson('/api/v1/behavioral-events', $this->payload($pet))
            ->assertUnauthorized();
    }

    #[Test]
    public function a_webhook_with_a_wrong_secret_is_rejected(): void
    {
        $pet = Pet::factory()->create();

        $this->withHeaders(['X-Webhook-Secret' => 'wrong'])
            ->postJson('/api/v1/behavioral-events', $this->payload($pet))
            ->assertUnauthorized();
    }

    #[Test]
    public function a_duplicate_event_id_is_idempotent(): void
    {
        $pet = Pet::factory()->create();
        $payload = $this->payload($pet);

        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $payload)->assertCreated();

        // Same event_id again — must update, not duplicate.
        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $payload)->assertOk();

        $this->assertSame(1, BehavioralEvent::count());
    }

    #[Test]
    public function a_webhook_for_a_nonexistent_pet_is_rejected(): void
    {
        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', [
                'event_id' => '11111111-1111-4111-8111-111111111111',
                'pet_id' => '00000000-0000-4000-8000-000000000000',
                'event_type' => 'pacing',
                'severity' => 'critical',
                'confidence_score' => 0.85,
                'recorded_at' => now()->toIso8601String(),
            ])->assertUnprocessable();
    }
}