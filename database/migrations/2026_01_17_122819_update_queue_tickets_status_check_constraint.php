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
        DB::statement('ALTER TABLE queue_tickets DROP CONSTRAINT IF EXISTS queue_tickets_status_check');

        // Add the new check constraint with CANCELLED status
        DB::statement("ALTER TABLE queue_tickets ADD CONSTRAINT queue_tickets_status_check CHECK (status IN ('WAITING', 'CALLED', 'SERVING', 'DONE', 'SKIPPED', 'CANCELLED'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original check constraint without CANCELLED
        DB::statement('ALTER TABLE queue_tickets DROP CONSTRAINT IF EXISTS queue_tickets_status_check');
        DB::statement("ALTER TABLE queue_tickets ADD CONSTRAINT queue_tickets_status_check CHECK (status IN ('WAITING', 'CALLED', 'SERVING', 'DONE', 'SKIPPED'))");
    }
};
