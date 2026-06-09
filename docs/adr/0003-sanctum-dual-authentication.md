# ADR-0003: Sanctum Dual-Authentication Strategy

**Status:** Accepted · Sprint 1, Day 5
**Context:** AT2 §3.3, FR-01

## Decision
A single `auth:sanctum` guard serves both client types. Authentication endpoints issue bearer tokens in the JSON response for all clients. The Next.js SPA supports session-cookie authentication via stateful domains, while the React Native client uses the bearer token exclusively.

## Rationale
- One guard handles both mechanisms transparently by inspecting incoming cookie jars or header values.
- Login failures return a generic error message for both mismatched passwords and nonexistent accounts to eliminate enumeration vulnerabilities.
- Registration settings prevent self-assignment of privileged positions by forcing default low-tier settings via Eloquent constraints.