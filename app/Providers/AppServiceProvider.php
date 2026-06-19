<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\HealthRecord;
use App\Models\Pet;
use App\Policies\HealthRecordPolicy;
use App\Policies\PetPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Pet::class, PetPolicy::class);
        Gate::policy(HealthRecord::class, HealthRecordPolicy::class);

        // ─── THE SECURE API LIMITER RULE ──────────────────────────────
        RateLimiter::for('api', function (Request $request) {
            // Capped at 60 requests/minute keyed by the authenticated user's ID, 
            // falling back to their client IP address for unauthenticated requests.
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });
    }
}