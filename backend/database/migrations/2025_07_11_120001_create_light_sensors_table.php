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
        Schema::create('light_sensors', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Light Sensor A", "Light Sensor B"
            $table->decimal('value', 8, 2); // Light intensity value with 2 decimal places (allows up to 999999.99)
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
        Schema::dropIfExists('light_sensors');
    }
}; 