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
        Schema::table('sensor_histories_excel_files', function (Blueprint $table) {
            $table->bigInteger('file_size')->nullable()->after('date');
            $table->integer('download_count')->default(0)->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sensor_histories_excel_files', function (Blueprint $table) {
            $table->dropColumn(['file_size', 'download_count']);
        });
    }
};
