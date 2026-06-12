<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\Auth\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\HealthRecordController;

Route::prefix('v1')->group(function () {
    // Public authentication endpoints
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    // Protected endpoints — require a valid Sanctum credential
    // (either session cookie for SPA or bearer token for mobile)
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
        Route::apiResource('health-records', HealthRecordController::class)
            ->only(['index', 'store', 'show', 'destroy']);
    });
});