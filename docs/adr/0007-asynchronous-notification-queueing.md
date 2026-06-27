# ADR-0007: Asynchronous Notification Queueing

**Status:** Accepted · Sprint 2, Day 10
**Context:** AT2 §3.3, R-03 (decouple the perception node from external I/O)

## Decision

FCM push dispatch is decoupled from webhook ingestion via Laravel's
event system with a queued listener (`ShouldQueue`). The controller
persists the event and dispatches `BehavioralEventDetected`; the
`SendBehavioralEventPush` listener handles the Firebase call on the
queue, outside the HTTP request that received the webhook.

## Rationale

1. **Perception-node responsiveness.** The Python CV service awaits the
   webhook's HTTP response. If the controller called Firebase
   synchronously, the CV node would block on Firebase's round-trip
   (typically 100–400ms, worse under failure) for every alert, stalling
   the video-processing loop. Returning immediately after the DB write
   keeps the webhook response fast, so the perception loop is never
   throttled by external I/O latency.

2. **Failure isolation.** A Firebase outage, timeout, or 5xx must not
   fail or roll back the event-ingestion transaction. The queued listener
   runs in a separate execution context: the event is durably persisted
   regardless of FCM availability, and a push failure is logged without
   affecting ingestion. The `FcmService` additionally catches all
   throwables internally as a second layer of isolation.

3. **Idempotency interaction.** The controller dispatches the event only
   when `wasRecentlyCreated` is true (see ADR-0005/Day 9 idempotency), so
   a retried webhook updates the row but does not enqueue a second push —
   the owner is not double-notified on a redelivery.

## Operational dependency (must be documented for deployment)

`ShouldQueue` requires a running queue worker (`php artisan queue:work`)
and `QUEUE_CONNECTION` set to `database` or `redis` in production. Under
`QUEUE_CONNECTION=sync` the listener runs synchronously (reintroducing
the latency this ADR avoids); with `database`/`redis` but no worker
running, pushes enqueue but never send. The `jobs` table migration
(framework-provided) backs the database queue driver.

## Consequences

- Sub-request-latency webhook responses; the CV node is never blocked.
- External-service failures are isolated from core ingestion.
- A deployment-time operational requirement (the queue worker) is
  introduced and must appear in the runbook and the AT4 deployment notes.

## Correction of an overstated claim

An earlier internal summary described "sub-millisecond response latency."
That is inaccurate — the webhook still performs a synchronous database
write (single-digit milliseconds) plus validation before responding. The
accurate claim is that the response excludes the Firebase round-trip, not
that it is sub-millisecond. Precision here matters for the viva.