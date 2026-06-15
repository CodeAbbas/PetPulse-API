<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Interim single-device token. A device_tokens table is the
            // correct multi-device model (see ADR-0006) — this column is
            // the minimal Sprint 2 path to a working push loop.
            $table->string('fcm_token', 512)->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('fcm_token');
        });
    }
};