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
        Schema::create('jurisprudence', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('case_number')->unique(); // Número de expediente o sentencia
            $table->string('court'); // Tribunal/Juzgado
            $table->string('jurisdiction')->nullable(); // Jurisdicción
            $table->date('decision_date')->nullable();
            $table->string('case_title');
            $table->text('summary'); // Resumen del caso
            $table->text('ruling'); // Fallo o decisión
            $table->text('legal_reasoning')->nullable(); // Fundamentos jurídicos
            $table->json('keywords')->nullable(); // ['accidente_transito', 'responsabilidad_civil', ...]
            $table->json('articles_cited')->nullable(); // Artículos legales citados
            $table->string('url')->nullable(); // URL de la fuente
            $table->text('full_text')->nullable(); // Texto completo

            // Para búsqueda semántica con pgvector en Supabase
            // Nota: pgvector se debe habilitar en Supabase con: CREATE EXTENSION vector;
            // El embedding se guardará como JSON por compatibilidad con Laravel
            $table->json('embedding')->nullable(); // Vector de embeddings para búsqueda semántica

            $table->enum('relevance_level', ['high', 'medium', 'low'])->default('medium');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('case_number');
            $table->index('court');
            $table->index('decision_date');
            $table->index('relevance_level');
            $table->fullText(['case_title', 'summary', 'legal_reasoning']); // Full-text search
        });

        // Nota: Para usar pgvector en Supabase, necesitarás ejecutar este SQL manualmente:
        // ALTER TABLE jurisprudence ADD COLUMN embedding_vector vector(1536);
        // CREATE INDEX ON jurisprudence USING ivfflat (embedding_vector vector_cosine_ops);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurisprudence');
    }
};
