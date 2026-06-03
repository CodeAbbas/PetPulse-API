<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pets', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('owner_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('name', 80);
            $table->enum('species', ['dog', 'cat'])->index();
            $table->string('breed', 100)->nullable();
            $table->enum('sex', ['male', 'female', 'unknown'])->default('unknown');
            $table->date('date_of_birth')->nullable();
            $table->string('microchip_number', 20)->nullable()->unique();

            $table->decimal('current_weight_kg', 6, 2)->nullable();
            $table->decimal('current_bmi', 5, 2)->nullable();
            $table->decimal('current_bmr_kcal', 8, 2)->nullable();

            $table->softDeletes();
            $table->timestamps();

            $table->index(['owner_user_id', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pets');
    }
};