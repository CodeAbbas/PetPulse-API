<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Pure domain service for server-side biometric computation.
 *
 * Encapsulates the BMI and BMR (Resting Energy Requirement) formulas
 * used during the HealthRecord persistence lifecycle. Has no dependency
 * on Eloquent, the HTTP layer, or the container — it is a deterministic,
 * side-effect-free calculator that can be unit-tested in isolation.
 *
 * Reference: AT2 §3.3, FR-03 (server-side biometric profile generation).
 *
 * Caveat (documented in ADR-0005): the BMI formula below is the human
 * weight/height² index applied as a morphological proxy. It is NOT a
 * clinically validated canine body-condition metric (which uses the
 * 9-point Body Condition Score). It is retained per project requirement
 * as a relative dimensional ratio, with this limitation acknowledged.
 */
final class BiometricCalculator
{
    /**
     * Number of decimal places for all rounded outputs.
     */
    private const PRECISION = 2;

    /**
     * BMR power-curve coefficient (Resting Energy Requirement).
     */
    private const BMR_COEFFICIENT = 70.0;

    /**
     * BMR power-curve exponent.
     */
    private const BMR_EXPONENT = 0.75;

    /**
     * Compute Body Mass Index from weight (kg) and height (cm).
     *
     * BMI = weight_kg / (height_cm / 100)^2
     *
     * Returns null when inputs are absent or non-positive, preventing
     * any divide-by-zero condition rather than throwing.
     */
    public function bmi(?float $weightKg, ?float $heightCm): ?float
    {
        if ($weightKg === null || $heightCm === null) {
            return null;
        }

        if ($weightKg <= 0.0 || $heightCm <= 0.0) {
            return null;
        }

        $heightMetres = $heightCm / 100.0;
        $bmi = $weightKg / ($heightMetres ** 2);

        return round($bmi, self::PRECISION);
    }

    /**
     * Compute Basal Metabolic Rate (Resting Energy Requirement) in kcal/day.
     *
     * BMR = 70 * (weight_kg)^0.75
     *
     * Returns null when weight is absent or non-positive.
     */
    public function bmr(?float $weightKg): ?float
    {
        if ($weightKg === null || $weightKg <= 0.0) {
            return null;
        }

        $bmr = self::BMR_COEFFICIENT * ($weightKg ** self::BMR_EXPONENT);

        return round($bmr, self::PRECISION);
    }
}