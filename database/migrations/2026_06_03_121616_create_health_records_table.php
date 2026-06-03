<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pet_id')
                  ->constrained('pets')
                  ->cascadeOnDelete()
                  ->index();

            $table->foreignUuid('recorded_by_user_id')
                  ->nullable()
                  ->constrained('users');

            $table->enum('record_type', [
                'weight',
                'vaccination',
                'examination',
                'lab_work',
                'dental',
                'surgery',
                'medication',
                'other',
            ])->index();

            $table->decimal('weight_kg', 6, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable();
            $table->decimal('bmr_kcal', 8, 2)->nullable();

            $table->string('summary', 255);
            $table->text('detail')->nullable();

            $table->date('recorded_at')->index();
            $table->timestamps();

            $table->index(['pet_id', 'recorded_at']);
            $table->index(['pet_id', 'record_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};