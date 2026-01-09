<?php

use App\Enums\QueueAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('queue_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('staff_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action', QueueAction::toArray());
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->timestamp('event_time');
            $table->text('remark')->nullable();

            $table->index('queue_ticket_id');
            $table->index('event_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_events');
    }
};
