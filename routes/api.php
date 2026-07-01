<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\BehavioralEventController;
use App\Http\Controllers\Api\V1\HealthRecordController;
use App\Http\Controllers\Api\V1\PetController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ClinicController;
use App\Http\Controllers\Api\V1\EhrTokenController;

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
    // FR-06: public EHR share-link viewer — NO auth (signed token is the gate)
    Route::get('ehr/{jwt}', [EhrTokenController::class, 'show'])
        ->where('jwt', '[A-Za-z0-9\-\_\.]+');

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
        // Smart Triage — emergency clinic directory (FR-08)
        Route::get('clinics', [ClinicController::class, 'index']);
        Route::get('clinics/{clinic}', [ClinicController::class, 'show']);
        // FR-05: vet issues a time-expiring EHR share link
        Route::post('pets/{pet}/ehr-token', [EhrTokenController::class, 'store']);
    });
});