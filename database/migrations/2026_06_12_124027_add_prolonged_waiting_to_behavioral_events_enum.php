<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            "ALTER TABLE behavioral_events MODIFY COLUMN event_type
             ENUM('pacing', 'presence', 'vocalization', 'rapid_zone_transition', 'prolonged_waiting')
             NOT NULL"
        );
    }

    public function down(): void
    {
        DB::statement(
            "ALTER TABLE behavioral_events MODIFY COLUMN event_type
             ENUM('pacing', 'presence', 'vocalization', 'rapid_zone_transition')
             NOT NULL"
        );
    }
};