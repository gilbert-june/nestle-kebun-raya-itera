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
        Schema::create('temperature_sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Sensor A", "Sensor B"
            $table->decimal('value', 5, 2); // Temperature value with 2 decimal places
            $table->timestamps();
            
            // Index for better query performance
            $table->index(['name', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temperature_sensors');
    }
};
