<?php

use App\Enums\DayOfWeek;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('poly_service_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('poly_id')->constrained()->onDelete('cascade');
            $table->enum('day_of_week', DayOfWeek::toArray());
            $table->time('open_time');
            $table->time('close_time');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['poly_id', 'day_of_week']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poly_service_hours');
    }
};
