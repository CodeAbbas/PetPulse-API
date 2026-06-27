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
     */
    public function index(): PetCollection
    {
        // Scope with metrics relation to feed the frontend data grid efficiently
        $pets = Pet::with(['metrics'])->latest()->get();
        
        return new PetCollection($pets);
    }

    /**
     * Store a newly created pet in the clinic registry.
     */
    public function store(StorePetRequest $request): JsonResponse
    {
        $data = $request->validated();

        // If an owner_id isn't explicitly defined by a vet, bind to the authenticated actor
        if (!isset($data['owner_id'])) {
            $data['owner_id'] = auth()->id();
        }

        $pet = Pet::create($data);

        // Initialize empty metrics row to preserve database structural bounds
        $pet->metrics()->create([
            'current_weight_kg' => null,
            'current_bmi' => null,
            'current_bmr_kcal' => null,
        ]);

        return (new PetResource($pet->load('metrics')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified pet profile.
     */
    public function show(Pet $pet): PetResource
    {
        return new PetResource($pet->load('metrics'));
    }

    /**
     * Update the specified pet profile in storage.
     */
    public function update(UpdatePetRequest $request, Pet $pet): PetResource
    {
        $pet->update($request->validated());

        return new PetResource($pet->load('metrics'));
    }

    /**
     * Remove the specified pet from the active clinic registry.
     */
    public function destroy(Pet $pet): JsonResponse
    {
        $pet->delete();

        return response()->json(null, 204);
    }
}