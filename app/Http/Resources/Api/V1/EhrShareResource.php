<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\EhrToken;
use App\Models\Pet;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The public EHR view returned by GET /ehr/{jwt} (FR-06).
 *
 * Deliberately projects only clinical data — no owner PII beyond what a
 * shared health record needs. The share metadata lets the viewer show
 * "shared by clinic, expires in X".
 *
 * @property Pet $resource
 */
final class EhrShareResource extends JsonResource
{
    public function __construct(Pet $pet, private readonly EhrToken $token)
    {
        parent::__construct($pet);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Pet $pet */
        $pet = $this->resource;

        return [
            'pet' => [
                'name' => $pet->name,
                'species' => $pet->species->value,
                'breed' => $pet->breed,
                'sex' => $pet->sex->value,
                'age_years' => $pet->age_years,
                'microchip_number' => $pet->microchip_number,
                'metrics' => [
                    'current_weight_kg' => $pet->current_weight_kg !== null
                        ? (float) $pet->current_weight_kg
                        : null,
                    'current_bmi' => $pet->current_bmi !== null
                        ? (float) $pet->current_bmi
                        : null,
                    'current_bmr_kcal' => $pet->current_bmr_kcal !== null
                        ? (float) $pet->current_bmr_kcal
                        : null,
                ],
            ],
            'health_records' => $pet->healthRecords->map(fn ($record) => [
                'id' => $record->id,
                'record_type' => $record->record_type?->value,
                'summary' => $record->summary,
                'detail' => $record->detail,
                'vitals' => [
                    'weight_kg' => $record->weight_kg !== null ? (float) $record->weight_kg : null,
                    'height_cm' => $record->height_cm !== null ? (float) $record->height_cm : null,
                    'temperature_c' => $record->temperature_c !== null ? (float) $record->temperature_c : null,
                    'heart_rate_bpm' => $record->heart_rate_bpm !== null ? (int) $record->heart_rate_bpm : null,
                ],
                'computed_metrics' => [
                    'bmi' => $record->bmi !== null ? (float) $record->bmi : null,
                    'bmr_kcal' => $record->bmr_kcal !== null ? (float) $record->bmr_kcal : null,
                ],
                'recorded_at' => $record->recorded_at?->toIso8601String(),
            ])->values(),
            'share' => [
                'issued_at' => $this->token->created_at?->toIso8601String(),
                'expires_at' => $this->token->expires_at->toIso8601String(),
                'is_first_access' => $this->token->first_accessed_at === null
                    || $this->token->first_accessed_at->equalTo($this->token->updated_at),
            ],
        ];
    }
}