<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Events\BehavioralEventDetected;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BehavioralEvent\StoreBehavioralEventRequest;
use App\Models\BehavioralEvent;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class BehavioralEventController extends Controller
{
    /**
     * Ingest a behavioural event webhook from the Python perception service.
     *
     * Idempotent: the perception service generates the event UUID, so a
     * retried dispatch (R-03 mitigation) updates rather than duplicates.
     */
    public function store(StoreBehavioralEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        $event = BehavioralEvent::updateOrCreate(
            ['id' => $data['event_id']],
            [
                'pet_id' => $data['pet_id'],
                'event_type' => $data['event_type'],
                'severity' => $data['severity'],
                'confidence_score' => $data['confidence_score'],
                'logged_at' => $data['recorded_at'],
                'is_read' => false,
                'owner_notified' => false,
            ],
        );

        // Only broadcast on first creation — a retried (idempotent)
        // webhook must NOT re-notify the owner.
        if ($event->wasRecentlyCreated) {
            BehavioralEventDetected::dispatch($event);
        }

        return response()->json(
            ['data' => ['event_id' => $event->id, 'status' => 'queued']],
            $event->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK,
        );
    }
}