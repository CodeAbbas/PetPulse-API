<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\HealthRecord\StoreHealthRecordRequest;
use App\Http\Resources\Api\V1\HealthRecordResource;
use App\Models\HealthRecord;
use App\Models\Pet;
use App\Services\BiometricCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

final class HealthRecordController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly BiometricCalculator $calculator,
    ) {}

    /**
     * List health records. Owners see only records for their own pets;
     * vets and admins see all.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', HealthRecord::class);

        $user = $request->user();

        $query = HealthRecord::query()
            ->with('pet:id,name,owner_user_id')
            ->orderByDesc('recorded_at');

        // Row-level scoping: owners are restricted to records for their pets.
        if ($user->isOwner()) {
            $query->whereHas('pet', function ($q) use ($user) {
                $q->where('owner_user_id', $user->id);
            });
        }

        return response()->json([
            'data' => HealthRecordResource::collection($query->paginate(15)),
        ]);
    }

    /**
     * Create a health record with server-computed BMI and BMR.
     */
    public function store(StoreHealthRecordRequest $request): JsonResponse
    {
        $this->authorize('create', HealthRecord::class);

        $data = $request->validated();
        $pet = Pet::findOrFail($data['pet_id']);

        $user = $request->user();

        // Owners may only log records for their own pets. Vets may log
        // for any pet. (Admins cannot create — blocked by the policy.)
        if ($user->isOwner() && $pet->owner_user_id !== $user->id) {
            abort(Response::HTTP_FORBIDDEN, 'You may only log records for your own pets.');
        }

        // Server-side biometric computation — never trusted from input.
        $weightKg = isset($data['weight_kg']) ? (float) $data['weight_kg'] : null;
        $heightCm = isset($data['height_cm']) ? (float) $data['height_cm'] : null;

        $data['bmi'] = $this->calculator->bmi($weightKg, $heightCm);
        $data['bmr_kcal'] = $this->calculator->bmr($weightKg);

        // Clinical-actor trace: when the author is a vet, record them as
        // the recording user. Owner-logged records carry the owner's id.
        $data['recorded_by_user_id'] = $user->id;
        if (! isset($data['recorded_at'])) {
            $data['recorded_at'] = now()->format('Y-m-d');
        }

        $record = HealthRecord::create($data);

        return (new HealthRecordResource($record->load('pet:id,name,owner_user_id')))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Show a single health record.
     */
    public function show(Request $request, HealthRecord $healthRecord): HealthRecordResource
    {
        $this->authorize('view', $healthRecord);

        return new HealthRecordResource($healthRecord->load('pet:id,name,owner_user_id'));
    }

    /**
     * Soft-delete is not applicable; only admins may hard-delete.
     */
    public function destroy(Request $request, HealthRecord $healthRecord): JsonResponse
    {
        $this->authorize('delete', $healthRecord);

        $healthRecord->delete();

        return response()->json([
            'data' => null,
            'meta' => ['message' => 'Health record deleted successfully.'],
        ]);
    }
}