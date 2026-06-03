<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ehr_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('pet_id')
                  ->constrained('pets')
                  ->cascadeOnDelete();

            $table->foreignUuid('issued_by_user_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            $table->string('jwt_hash', 64)->unique();

            $table->timestamp('expires_at')->index();
            $table->timestamp('first_accessed_at')->nullable();
            $table->timestamp('revoked_at')->nullable()->index();

            $table->string('accessed_by_ip', 45)->nullable();
            $table->string('accessed_by_user_agent', 500)->nullable();

            $table->timestamps();

            $table->index(['pet_id', 'expires_at', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ehr_tokens');
    }
};