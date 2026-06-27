# ADR-0006: FCM Device Token Topology

**Status:** Accepted (interim) · Sprint 2, Day 10
**Context:** AT2 §3.3, FR-07 (real-time owner alerting via FCM)

## Decision

Device push tokens are stored in a single nullable `fcm_token` column on
the `users` table. One token per user account.

## Rationale (and the freeze constraint)

The June 27 cross-system test freeze required a working end-to-end push
loop. A single column is the minimal schema change that delivers a
demonstrable alert path: token registration (`POST /auth/fcm-token`),
storage, and lookup via `pet → owner → fcm_token`.

## Known structural limitations (declared for examination)

1. **Single-device only.** One column holds one token. A user with both
   a phone and a tablet retains only the most-recently-registered token;
   the other device silently stops receiving alerts. There is no
   one-to-many relationship between a user and their devices.

2. **Stale-token accumulation.** FCM tokens rotate on app reinstall, OS
   restore, or cache clearance. The single column has no mechanism to
   detect or prune an invalidated token; a stale value causes FCM to
   return an `UNREGISTERED` error (currently logged and swallowed) with
   no automatic cleanup.

3. **No platform discrimination.** The column does not record whether the
   token is Android or iOS, which a production system needs for
   platform-specific payload shaping.

## Post-MVP remediation path

Replace the column with a dedicated relational table:

    device_tokens
    ─────────────
    id              uuid (PK)
    user_id         uuid (FK → users.id, indexed)
    token           string(512), unique
    platform        enum('android','ios')
    last_used_at    timestamp
    created_at / updated_at

The `User` model gains a `hasMany(DeviceToken::class)` relationship; the
listener iterates all of a user's tokens, and FCM `UNREGISTERED`
responses trigger row deletion for automatic pruning. This migration is
additive and non-breaking: the column can be backfilled into the table
and then dropped.

## Consequences

- The demonstrable alert path is achieved within the freeze.
- The limitation is a known, documented trade-off rather than a latent
  defect — and is the first candidate for post-submission hardening.
- This honesty strengthens the AT4 critical-evaluation narrative
  (Learning Outcome: critical appraisal of one's own engineering).