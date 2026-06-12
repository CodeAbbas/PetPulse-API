<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\HealthRecord;
use App\Models\Pet;
use App\Policies\HealthRecordPolicy;
use App\Policies\PetPolicy;
use Illuminate\Support\Facades\Gate;
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
    }
}