<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClinicResource;
use App\Models\Clinic;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClinicController extends Controller
{
    /**
     * List clinics for the mobile Smart Triage screen (FR-08).
     *
     * Query parameters:
     *   emergency=1            → only is_emergency_24_7 clinics
     *   lat=<float>&lng=<float> → sort by proximity, attach distance_km
     *
     * When coordinates are supplied, results are ordered nearest-first
     * using the Haversine great-circle formula computed in SQL. This is
     * sufficient for the prototype's clinic volume; a spatial index /
     * PostGIS would be the production scaling path.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'emergency' => ['sometimes', 'boolean'],
            'lat' => ['sometimes', 'numeric', 'between:-90,90'],
            'lng' => ['sometimes', 'numeric', 'between:-180,180'],
        ]);

        $query = Clinic::query();

        // FR-08: filter to 24/7 emergency clinics when requested.
        if ($request->boolean('emergency')) {
            $query->where('is_emergency_24_7', true);
        }

        $lat = $validated['lat'] ?? null;
        $lng = $validated['lng'] ?? null;

        if ($lat !== null && $lng !== null) {
            // Haversine distance in kilometres (Earth radius 6371 km).
            $haversine = '(6371 * acos('
                . 'cos(radians(?)) * cos(radians(latitude)) * '
                . 'cos(radians(longitude) - radians(?)) + '
                . 'sin(radians(?)) * sin(radians(latitude))'
                . '))';

            $query->select('*')
                ->selectRaw("{$haversine} AS distance_km", [$lat, $lng, $lat])
                ->orderBy('distance_km');
        } else {
            // No coordinates: emergency clinics first, then by rating.
            $query->orderByDesc('is_emergency_24_7')
                ->orderByDesc('rating');
        }

        return ClinicResource::collection($query->get());
    }

    /**
     * Show a single clinic profile.
     */
    public function show(Clinic $clinic): ClinicResource
    {
        return new ClinicResource($clinic);
    }
}