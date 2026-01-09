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
        Schema::create('queue_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('poly_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('code_prefix', 5);
            $table->string('service_unit')->nullable();
            $table->integer('avg_service_minutes')->default(15);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['code_prefix', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_types');
    }
};
