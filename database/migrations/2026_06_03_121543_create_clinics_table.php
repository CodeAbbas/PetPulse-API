<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinics', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('vet_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->string('name', 200);
            $table->string('address_line_1', 200);
            $table->string('address_line_2', 200)->nullable();
            $table->string('city', 100);
            $table->string('postcode', 20)->index();
            $table->string('country_code', 2)->default('GB');

            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            $table->string('phone_e164', 20);
            $table->boolean('is_emergency_24_7')->default(false)->index();
            $table->decimal('rating', 3, 2)->nullable();

            $table->timestamps();
        });

        Schema::table('clinics', function (Blueprint $table) {
            $table->index(['is_emergency_24_7', 'country_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinics');
    }
};