<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class AuthController extends Controller
{
    /**
     * Register a new owner account and issue an access token.
     *
     * Role is forced to 'owner' — privileged roles (vet, admin) are
     * never self-assignable via public registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create($request->validated());
        // Role defaults to 'owner' via the model's $attributes default;
        // it is NOT taken from request input, even if supplied.

        $token = $user->createToken(
            $request->string('device_name')->value() ?: 'default-device'
        )->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Authenticate an existing user and issue an access token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! password_verify($request->string('password')->value(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken(
            $request->string('device_name')->value() ?: 'default-device'
        )->plainTextToken;

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Revoke the current access token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        // Revoke the token from the database persistence tier exclusively
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Logged out successfully.',
            ],
        ]);
    }

    /**
     * Return the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                ],
            ],
        ]);
    }
}