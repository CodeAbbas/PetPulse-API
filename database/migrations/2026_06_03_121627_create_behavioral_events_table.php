<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('behavioral_events', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pet_id')
                  ->constrained('pets')
                  ->cascadeOnDelete();

            $table->enum('event_type', [
                'pacing',
                'presence',
                'vocalization',
                'rapid_zone_transition',
            ])->index();

            $table->enum('severity', [
                'critical',
                'warning',
                'info',
            ])->default('info')->index();

            $table->decimal('confidence_score', 4, 3);
            $table->string('zone_name', 50)->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->string('snapshot_path', 500)->nullable();

            $table->boolean('is_read')->default(false);
            $table->boolean('owner_notified')->default(false);

            $table->timestamp('logged_at')->index();
            $table->timestamps();

            $table->index(['pet_id', 'logged_at']);
            $table->index(['pet_id', 'is_read', 'severity']);
            $table->index(['pet_id', 'severity', 'logged_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('behavioral_events');
    }
};