<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\BehavioralEventDetected;
use App\Services\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Queued listener: sends an FCM push for a detected behavioural event.
 *
 * Resolves event → pet → owner → fcm_token, skipping cleanly if any
 * link is absent. Runs on the queue so Firebase latency never blocks
 * the webhook ingestion path.
 */
final class SendBehavioralEventPush implements ShouldQueue
{
    public function __construct(
        private readonly FcmService $fcm,
    ) {}

    public function handle(BehavioralEventDetected $event): void
    {
        $behavioralEvent = $event->event->loadMissing('pet.owner');

        $pet = $behavioralEvent->pet;
        $owner = $pet?->owner;
        $token = $owner?->fcm_token;

        if ($pet === null || $owner === null || empty($token)) {
            return;
        }

        $petName = $pet->name;
        $eventLabel = $this->humanReadable($behavioralEvent->event_type->value);
        $body = "{$petName} has been detected {$eventLabel}.";

        $delivered = $this->fcm->sendToToken(
            deviceToken: $token,
            title: 'PetPulse Alert',
            body: $body,
            data: [
                'event_id' => $behavioralEvent->id,
                'pet_id' => $pet->id,
                'event_type' => $behavioralEvent->event_type->value,
                'severity' => $behavioralEvent->severity->value,
            ],
        );

        // Audit trail: flip the flag only on confirmed delivery, so the
        // column reflects alerts that genuinely reached a device rather
        // than ones merely attempted.
        if ($delivered) {
            $behavioralEvent->update(['owner_notified' => true]);
        }
    }

    private function humanReadable(string $eventType): string
    {
        return match ($eventType) {
            'pacing' => 'pacing by the door',
            'prolonged_waiting' => 'waiting by the door for a prolonged period',
            'vocalization' => 'vocalising (barking or whining)',
            'rapid_zone_transition' => 'moving rapidly near the door',
            'presence' => 'lingering near the door',
            default => 'showing signs of distress',
        };
    }
}