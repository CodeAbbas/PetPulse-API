<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EhrShareResource;
use App\Models\EhrToken;
use App\Models\Pet;
use App\Services\EhrTokenService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

final class EhrTokenController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private readonly EhrTokenService $tokens)
    {
    }

    /**
     * FR-05: issue a time-expiring EHR share link for a pet.
     *
     * Vet/admin only (owners cannot mint share links in this design). The
     * raw JWT is returned ONCE; only its SHA-256 hash is persisted.
     */
    public function store(Request $request, Pet $pet): JsonResponse
    {
        $user = $request->user();

        // Only clinical staff may issue share links.
        if ($user->isOwner()) {
            abort(Response::HTTP_FORBIDDEN, 'Only clinical staff may issue EHR share links.');
        }

        $validated = $request->validate([
            'ttl_hours' => ['sometimes', 'integer', 'min:1', 'max:168'],
        ]);
        $ttlHours = $validated['ttl_hours'] ?? 72;

        $issued = $this->tokens->issue($pet->id, $ttlHours);

        EhrToken::create([
            'id' => $issued['jti'],
            'pet_id' => $pet->id,
            'issued_by_user_id' => $user->id,
            'jwt_hash' => $this->tokens->hash($issued['jwt']),
            'expires_at' => $issued['expires_at'],
        ]);

        // The frontend base URL where the public viewer lives.
        $shareBase = rtrim((string) config('app.frontend_url', 'http://localhost:3000'), '/');
        $shareUrl = "{$shareBase}/ehr/{$issued['jwt']}";

        return response()->json([
            'data' => [
                'share_url' => $shareUrl,
                'token' => $issued['jwt'],
                'expires_at' => $issued['expires_at']->toIso8601String(),
                'ttl_hours' => $ttlHours,
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * FR-06: publicly decode an EHR share link and return the pet's record.
     *
     * NO authentication — this route is intentionally public so a link can
     * be opened by anyone the vet shares it with. Security rests on the
     * signed, hashed, time-expiring token. First access is audit-logged
     * (IP, user agent, timestamp).
     */
    public function show(Request $request, string $jwt): JsonResponse
    {
        // 1. Signature + expiry + purpose validation.
        $claims = $this->tokens->verify($jwt);
        if ($claims === null) {
            return response()->json([
                'message' => 'This share link is invalid or has expired.',
            ], Response::HTTP_NOT_FOUND);
        }

        // 2. Look the token up by its hash (raw token never stored).
        $token = EhrToken::query()
            ->where('jwt_hash', $this->tokens->hash($jwt))
            ->first();

        if ($token === null || ! $token->isActive()) {
            return response()->json([
                'message' => 'This share link is invalid or has been revoked.',
            ], Response::HTTP_NOT_FOUND);
        }

        // 3. Audit-log first access (IP + UA + timestamp), once.
        if ($token->first_accessed_at === null) {
            $token->forceFill([
                'first_accessed_at' => Carbon::now(),
                'accessed_by_ip' => $request->ip(),
                'accessed_by_user_agent' => substr((string) $request->userAgent(), 0, 500),
            ])->save();
        }

        // 4. Load the pet + its health records and return the shared EHR.
        $pet = Pet::query()
            ->with(['healthRecords' => fn ($q) => $q->orderByDesc('recorded_at')])
            ->find($token->pet_id);

        if ($pet === null) {
            return response()->json([
                'message' => 'The record for this share link is no longer available.',
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            'data' => new EhrShareResource($pet, $token),
        ]);
    }
}