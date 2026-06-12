<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            // New clinical vitals captured at logging time.
            $table->decimal('height_cm', 6, 2)->nullable()->after('weight_kg');
            $table->decimal('temperature_c', 4, 1)->nullable()->after('bmr_kcal');
            $table->unsignedSmallInteger('heart_rate_bpm')->nullable()->after('temperature_c');
        });
    }

    public function down(): void
    {
        Schema::table('health_records', function (Blueprint $table) {
            $table->dropColumn(['height_cm', 'temperature_c', 'heart_rate_bpm']);
        });
    }
};