# ADR-0005: Server-Side Biometric Computation for Health Records

**Status:** Accepted · Sprint 1, Day 7
**Context:** AT2 §3.3, FR-03 (real-time biometric profile generation)

## Decision

BMI and BMR are computed server-side during the HealthRecord
persistence lifecycle by an isolated, dependency-free domain service
(BiometricCalculator). Client-supplied BMI/BMR values are never trusted
and are not accepted as input.

## Formulas

- BMR (Resting Energy Requirement): 70 × weight_kg^0.75. This is the
  standard veterinary RER power curve and is clinically valid.
- BMI: weight_kg / (height_cm/100)^2.

## Known limitation (declared honestly for AT4)

The BMI formula is the HUMAN weight/height² index. It is NOT a
clinically validated canine/feline body-condition metric — veterinary
practice uses the 9-point Body Condition Score, which is not height-
derived. The BMI here is retained per project specification as a
relative "morphological proxy index" only. This limitation is
acknowledged rather than concealed, and would be the first candidate
for replacement in a production veterinary system.

## Precision

All computed values are rounded to 2 decimal places (financial-style
rounding via PHP round()). The calculator is null-safe: absent or
non-positive inputs return null rather than throwing, preventing
divide-by-zero conditions.

## Schema decision

Day 3's health_records migration is immutable. Rather than rename
columns to match the Day 7 spec, an additive migration added the new
clinical vitals (height_cm, temperature_c, heart_rate_bpm). Existing
columns (bmi, bmr_kcal, recorded_by_user_id) were reused. The public
input field 'notes' is adapted to the schema column 'detail' in the
form request's validated() override.

## Authorisation deviations from spec

- authorizeResource() was replaced with per-method $this->authorize()
  calls, because the controller implements a deliberately partial
  method set (index, store, show, destroy) and authorizeResource
  assumes all seven resource actions.
- The "owner may only create for own pets" rule is enforced in the
  controller (not the policy), because Laravel's create() ability is
  class-level and receives no model instance to check ownership against.

## Role matrix (health records)

| Action  | Owner            | Vet                       | Admin |
|---------|------------------|---------------------------|-------|
| viewAny | ✓ (own pets)     | ✓ (all)                   | ✓     |
| view    | ✓ (own pets)     | ✓ (all)                   | ✓     |
| create  | ✓ (own pets)     | ✓ (any pet)               | ✗     |
| update  | ✗                | ✓ (own authored only)*    | ✗     |
| delete  | ✗                | ✗                         | ✓     |

*update policy is defined but not yet routed — deferred pending the
 "administrative window" time-bound specification.

## Consequences

- The BiometricCalculator is reusable by any future feature needing
  the same metrics (e.g. the metabolic trajectory chart from FR-09).
- Clinical records are not deletable by their authors (audit integrity).