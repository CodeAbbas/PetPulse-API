<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Transforms a Pet model into the JSON contract the frontend expects.
 *
 * The metrics (current_weight_kg, current_bmi, current_bmr_kcal) are flat
 * columns on the pets table, NOT a separate relationship. This resource
 * reshapes them into the nested `metrics` object the frontend types define.
 *
 * The owner is projected as { id } only — PII-minimal per GDPR Art 5(c).
 */
class PetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species->value,
            'breed' => $this->breed,
            'sex' => $this->sex->value,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'age_years' => $this->age_years, // computed accessor on the model
            'microchip_number' => $this->microchip_number,

            // Flat columns reshaped into a nested object for the frontend.
            'metrics' => [
                'current_weight_kg' => $this->current_weight_kg,
                'current_bmi' => $this->current_bmi,
                'current_bmr_kcal' => $this->current_bmr_kcal,
            ],

            // PII-minimal owner projection.
            'owner' => [
                'id' => $this->owner_user_id,
            ],

            'timestamps' => [
                'created_at' => $this->created_at?->toIso8601String(),
                'updated_at' => $this->updated_at?->toIso8601String(),
            ],
        ];
    }
}