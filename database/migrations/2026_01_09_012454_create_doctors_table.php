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
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('poly_id')->constrained()->onDelete('cascade');
            $table->string('sip_number')->unique();
            $table->string('name');
            $table->string('specialization')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('poly_id');
            $table->index('sip_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
