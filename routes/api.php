<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BehavioralEventController;
use App\Http\Controllers\Api\V1\HealthRecordController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // ─── Public Authentication Endpoints ─────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // ─── Machine-to-Machine Edge AI Webhook ──────────────────────────────────
    // Protected by constant-time secret signature verification (Stateless M2M)
    Route::middleware('webhook.secret')->group(function () {
        Route::post('behavioral-events', [BehavioralEventController::class, 'store']);
    });

    // ─── Protected Core Application Subsurface ────────────────────────────────
    // Requires authenticated bearer tokens (Mobile) or stateful SPA cookies (Web)
    Route::middleware('auth:sanctum')->group(function () {
        
        // Session Identity Management
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });

        // Clinic Patient Registry Resources
        // (Include additional endpoints as built out during your upcoming sprints)
        Route::apiResource('health-records', HealthRecordController::class)
            ->only(['index', 'store', 'show', 'destroy']);
        Route::post('auth/fcm-token', [AuthController::class, 'updateFcmToken']);  
    });
});