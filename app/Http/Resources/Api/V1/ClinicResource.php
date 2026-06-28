<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a Clinic for the mobile Smart Triage screen (FR-08).
 *
 * Includes a `distance_km` field when the query computed proximity from
 * client-supplied coordinates; null otherwise.
 */
class ClinicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'address' => [
                'line_1' => $this->address_line_1,
                'line_2' => $this->address_line_2,
                'city' => $this->city,
                'postcode' => $this->postcode,
                'country_code' => $this->country_code,
            ],
            'location' => [
                'latitude' => $this->latitude,
                'longitude' => $this->longitude,
            ],
            'phone_e164' => $this->phone_e164,
            'is_emergency_24_7' => $this->is_emergency_24_7,
            'rating' => $this->rating,
            // Present only when proximity was computed (see controller).
            'distance_km' => $this->when(
                isset($this->distance_km),
                fn () => round((float) $this->distance_km, 2),
            ),
        ];
    }
}