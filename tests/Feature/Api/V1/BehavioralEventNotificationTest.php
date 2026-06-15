<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Events\BehavioralEventDetected;
use App\Listeners\SendBehavioralEventPush;
use App\Models\BehavioralEvent;
use App\Models\Pet;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BehavioralEventNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.webhook.secret' => self::SECRET,
            'services.fcm.project_id' => 'test-project',
        ]);
    }

    private function webhookPayload(Pet $pet): array
    {
        return [
            'event_id' => '22222222-2222-4222-8222-222222222222',
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
            'severity' => 'critical',
            'confidence_score' => 0.85,
            'recorded_at' => now()->toIso8601String(),
        ];
    }

    // ─── Controller Event Dispatch Layer ─────────────────────────

    #[Test]
    public function a_webhook_creates_the_event_and_dispatches_the_notification(): void
    {
        Event::fake([BehavioralEventDetected::class]);

        $pet = Pet::factory()->create();

        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $this->webhookPayload($pet))
            ->assertCreated();

        $this->assertDatabaseHas('behavioral_events', ['pet_id' => $pet->id]);

        Event::assertDispatched(
            BehavioralEventDetected::class,
            fn (BehavioralEventDetected $e) => $e->event->pet_id === $pet->id,
        );
    }

    #[Test]
    public function an_idempotent_retry_does_not_dispatch_a_second_notification(): void
    {
        Event::fake([BehavioralEventDetected::class]);

        $pet = Pet::factory()->create();
        $payload = $this->webhookPayload($pet);

        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $payload)->assertCreated();

        $this->withHeaders(['X-Webhook-Secret' => self::SECRET])
            ->postJson('/api/v1/behavioral-events', $payload)->assertOk();

        Event::assertDispatchedTimes(BehavioralEventDetected::class, 1);
    }

    // ─── Queued Listener Notification Verification Layer ──────────

    #[Test]
    public function the_listener_sends_an_fcm_push_via_a_mocked_service(): void
    {
        $owner = User::factory()->create(['fcm_token' => 'device-token-abc']);
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id, 'name' => 'Luna']);

        $event = BehavioralEvent::create([
            'id' => '33333333-3333-4333-8333-333333333333',
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
            'severity' => 'critical',
            'confidence_score' => 0.85,
            'logged_at' => now(),
            'is_read' => false,
            'owner_notified' => false,
        ]);

        $mock = $this->mock(FcmService::class);
        $mock->shouldReceive('sendToToken')
            ->once()
            ->withArgs(function (string $token, string $title, string $body, array $data) {
                return $token === 'device-token-abc'
                    && str_contains($body, 'Luna')
                    && $data['event_type'] === 'pacing';
            })
            ->andReturnTrue();

        (new SendBehavioralEventPush($mock))->handle(new BehavioralEventDetected($event));
    }

    #[Test]
    public function the_listener_skips_silently_when_the_owner_has_no_token(): void
    {
        $owner = User::factory()->create(['fcm_token' => null]);
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $event = BehavioralEvent::create([
            'id' => '44444444-4444-4444-8444-444444444444',
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
            'severity' => 'critical',
            'confidence_score' => 0.85,
            'logged_at' => now(),
            'is_read' => false,
            'owner_notified' => false,
        ]);

        $mock = $this->mock(FcmService::class);
        $mock->shouldNotReceive('sendToToken');

        (new SendBehavioralEventPush($mock))->handle(new BehavioralEventDetected($event));
    }

    #[Test]
    public function a_failed_fcm_handshake_does_not_throw(): void
    {
        $owner = User::factory()->create(['fcm_token' => 'device-token-xyz']);
        $pet = Pet::factory()->create(['owner_user_id' => $owner->id]);

        $event = BehavioralEvent::create([
            'id' => '55555555-5555-4555-8555-555555555555',
            'pet_id' => $pet->id,
            'event_type' => 'pacing',
            'severity' => 'critical',
            'confidence_score' => 0.85,
            'logged_at' => now(),
            'is_read' => false,
            'owner_notified' => false,
        ]);

        $mock = $this->mock(FcmService::class);
        $mock->shouldReceive('sendToToken')
            ->once() // Asserts the method was triggered
            ->andReturnFalse();

        (new SendBehavioralEventPush($mock))->handle(new BehavioralEventDetected($event));

        // Explicitly tells PHPUnit the test succeeded by reaching this line
        $this->assertTrue(true);
    }
}