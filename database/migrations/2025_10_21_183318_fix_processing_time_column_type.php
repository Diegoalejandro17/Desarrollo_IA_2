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
        Schema::table('cases_analysis', function (Blueprint $table) {
            // Cambiar processing_time de integer a decimal para soportar milisegundos
            $table->decimal('processing_time', 10, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cases_analysis', function (Blueprint $table) {
            // Revertir a integer
            $table->integer('processing_time')->nullable()->change();
        });
    }
};
