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
        Schema::create('legal_cases', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->text('description');
            $table->enum('case_type', [
                'civil',
                'penal',
                'laboral',
                'administrativo',
                'constitucional',
                'otro'
            ])->default('otro');
            $table->enum('status', [
                'draft',
                'analyzing',
                'analyzed',
                'archived'
            ])->default('draft');
            $table->json('parties')->nullable(); // {plaintiff: "", defendant: ""}
            $table->date('incident_date')->nullable();
            $table->text('facts')->nullable(); // Hechos del caso
            $table->json('metadata')->nullable(); // Datos adicionales flexibles
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();

            // Índices para búsqueda rápida
            $table->index('status');
            $table->index('case_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legal_cases');
    }
};
