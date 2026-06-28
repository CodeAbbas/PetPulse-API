<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Pet\StorePetRequest;
use App\Http\Requests\Api\V1\Pet\UpdatePetRequest;
use App\Http\Resources\Api\V1\PetCollection;
use App\Http\Resources\Api\V1\PetResource;
use App\Models\Pet;
use Illuminate\Http\JsonResponse;

class PetController extends Controller
{
    /**
     * Display a listing of the clinic patients.
     *
     * No eager-load needed for metrics — current_weight_kg, current_bmi,
     * and current_bmr_kcal are flat columns on the pets table, NOT a
     * separate relationship. PetResource reshapes them into a nested
     * `metrics` object for the frontend contract.
     */
    public function index(): PetCollection
    {
        $pets = Pet::latest()->get();

        return new PetCollection($pets);
    }

    /**
     * Store a newly created pet in the clinic registry.
     *
     * owner_user_id is derived from the authenticated user — it is NOT
     * accepted from request input, preventing ownership forgery.
     */
    public function store(StorePetRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Bind ownership to the authenticated actor; never from input.
        $data['owner_user_id'] = auth()->id();

        $pet = Pet::create($data);

        return (new PetResource($pet))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified pet profile.
     */
    public function show(Pet $pet): PetResource
    {
        return new PetResource($pet);
    }

    /**
     * Update the specified pet profile in storage.
     */
    public function update(UpdatePetRequest $request, Pet $pet): PetResource
    {
        $pet->update($request->validated());

        return new PetResource($pet->fresh());
    }

    /**
     * Remove the specified pet from the active clinic registry (soft-delete).
     */
    public function destroy(Pet $pet): JsonResponse
    {
        $pet->delete();

        return response()->json(null, 204);
    }
}