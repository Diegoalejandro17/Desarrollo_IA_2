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
        Schema::create('cases_analysis', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('legal_case_id')->constrained('legal_cases')->onDelete('cascade');

            // Estado del análisis
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed'
            ])->default('pending');

            // Resultados de cada agente
            $table->json('coordinator_result')->nullable(); // Resultado del agente coordinador
            $table->json('jurisprudence_result')->nullable(); // Precedentes encontrados
            $table->json('visual_analysis_result')->nullable(); // Análisis de evidencia visual
            $table->json('arguments_result')->nullable(); // Líneas argumentales generadas

            // Análisis integrado
            $table->json('legal_elements')->nullable(); // Elementos legales clave identificados
            $table->json('relevant_precedents')->nullable(); // Array de precedentes relevantes con score
            $table->json('defense_lines')->nullable(); // Líneas de defensa sugeridas
            $table->json('alternative_scenarios')->nullable(); // Escenarios alternativos

            // Métricas y metadatos
            $table->json('confidence_scores')->nullable(); // Scores de confianza por sección
            $table->integer('processing_time')->nullable(); // Tiempo de procesamiento en segundos
            $table->json('agent_execution_log')->nullable(); // Log de ejecución de agentes
            $table->text('executive_summary')->nullable(); // Resumen ejecutivo del análisis

            // Control de versiones
            $table->integer('version')->default(1);
            $table->foreignId('previous_analysis_id')->nullable()->constrained('cases_analysis')->onDelete('set null');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('legal_case_id');
            $table->index('status');
            $table->index(['legal_case_id', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cases_analysis');
    }
};
