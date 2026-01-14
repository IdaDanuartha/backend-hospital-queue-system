<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->integer('actual_service_minutes')->nullable()
                ->comment('Actual service time in minutes');
            $table->timestamp('service_started_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('queue_tickets', function (Blueprint $table) {
            $table->dropColumn(['actual_service_minutes', 'service_started_at']);
        });
    }
};
