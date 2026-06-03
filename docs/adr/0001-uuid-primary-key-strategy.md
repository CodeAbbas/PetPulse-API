# ADR-0001: UUID Primary Key Strategy

**Status:** Accepted · Sprint 1, Day 3
**Context:** AT2 §3.3, NFR-SEC-02

## Decision

All primary keys across the six core entities are UUID v4, stored as
`CHAR(36)` columns in MySQL 8. Models use a project-local trait
(`App\Models\Concerns\HasUuidPrimaryKey`) wrapping Laravel's UUID
generation rather than calling `Str::uuid()` directly in controllers.

## Rationale

1. Enumeration resistance: sequential integers expose cardinality and
   permit ID-guessing attacks against URL parameters.
2. Distributed generation: the Python perception service must be able
   to assign IDs to `behavioral_events` prior to webhook dispatch
   without a database round-trip, materially contributing to NFR-PERF-01.
3. Future-proofing: a single trait point of control enables migration
   to UUID v7 (time-ordered, improved index locality) without touching
   every model.

## Consequences

- **Cost:** Write amplification on InnoDB clustered indexes is non-zero
  due to non-sequential inserts. Acceptable at prototype scale; will be
  reviewed if dataset exceeds ~1M rows in any entity.
- **Storage:** 36-byte string keys vs 8-byte bigints. Trivial at scale
  of this project.
- **Joins:** Slightly slower than integer joins, but the API exposes
  paginated, indexed query patterns that mitigate this.

## Reconsider when

- A single entity exceeds 5M rows
- A measured query plan shows the UUID primary key as the bottleneck
- A future Sprint introduces real-time analytics requiring sequential keys