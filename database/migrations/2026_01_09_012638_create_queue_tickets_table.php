<?php

use App\Enums\QueueStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('queue_type_id')->constrained()->onDelete('cascade');
            $table->date('service_date');
            $table->integer('queue_number');
            $table->string('display_number', 20);
            $table->timestamp('issued_at');
            $table->enum('status', QueueStatus::toArray())->default(QueueStatus::WAITING);
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->foreignId('handled_by_staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->boolean('is_priority')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['queue_type_id', 'service_date', 'queue_number']);
            $table->index(['service_date', 'status']);
            $table->index('display_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
