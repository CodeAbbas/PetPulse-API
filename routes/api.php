<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BehavioralEventController;
use App\Http\Controllers\Api\V1\HealthRecordController;
use App\Http\Controllers\Api\V1\PetController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    
    // ─── Public Authentication Endpoints ─────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // ─── Machine-to-Machine Edge AI Webhook ──────────────────────────────────
    // 💡 INTENTIONALLY UNTHROTTLED (ADR-0007): Protected via signature secret handshake 
    // to prevent high-frequency CV telemetry bursts from dropping during acute panic events.
    Route::middleware('webhook.secret')->group(function () {
        Route::post('behavioral-events', [BehavioralEventController::class, 'store']);
    });

    // ─── Protected Core Application Subsurface ────────────────────────────────
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        
        // Session & Push Token Identity Management
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
            Route::post('fcm-token', [AuthController::class, 'updateFcmToken']);
        });

        // Clinic Patient Registry Resources
        Route::apiResource('pets', PetController::class);
        
        Route::apiResource('health-records', HealthRecordController::class)
            ->only(['index', 'store', 'show', 'destroy']);
    });
});