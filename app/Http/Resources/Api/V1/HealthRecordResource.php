<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\HealthRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin HealthRecord
 */
final class HealthRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'record_type' => $this->record_type?->value,
            'vitals' => [
                'weight_kg' => $this->weight_kg !== null ? (float) $this->weight_kg : null,
                'height_cm' => $this->height_cm !== null ? (float) $this->height_cm : null,
                'temperature_c' => $this->temperature_c !== null ? (float) $this->temperature_c : null,
                'heart_rate_bpm' => $this->heart_rate_bpm !== null ? (int) $this->heart_rate_bpm : null,
            ],
            'computed_metrics' => [
                'bmi' => $this->bmi !== null ? (float) $this->bmi : null,
                'bmr_kcal' => $this->bmr_kcal !== null ? (float) $this->bmr_kcal : null,
            ],
            'summary' => $this->summary,
            'detail' => $this->detail,
            'pet' => [
                'id' => $this->pet_id,
                'name' => $this->whenLoaded('pet', fn () => $this->pet->name),
            ],
            'recorded_by' => [
                'id' => $this->recorded_by_user_id,
            ],
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}