<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing check constraint
        DB::statement('ALTER TABLE queue_events DROP CONSTRAINT IF EXISTS queue_events_action_check');

        // Add the new check constraint with CANCEL action
        DB::statement("ALTER TABLE queue_events ADD CONSTRAINT queue_events_action_check CHECK (action IN ('CALL_NEXT', 'RECALL', 'SKIP', 'START_SERVICE', 'FINISH', 'CANCEL'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original check constraint without CANCEL
        DB::statement('ALTER TABLE queue_events DROP CONSTRAINT IF EXISTS queue_events_action_check');
        DB::statement("ALTER TABLE queue_events ADD CONSTRAINT queue_events_action_check CHECK (action IN ('CALL_NEXT', 'RECALL', 'SKIP', 'START_SERVICE', 'FINISH'))");
    }
};
