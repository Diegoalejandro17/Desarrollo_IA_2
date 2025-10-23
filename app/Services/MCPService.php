<?php

namespace App\Services;

use App\Models\LegalCase;
use App\Models\Evidence;
use App\Models\Jurisprudence;

/**
 * Model Context Protocol Service
 *
 * Construye el contexto estructurado para los agentes A2A
 */
class MCPService
{
    /**
     * Construir contexto completo de un caso legal
     */
    public function buildCaseContext(LegalCase $case): array
    {
        return [
            'case_id' => $case->uuid,
            'title' => $case->title,
            'description' => $case->description,
            'type' => $case->case_type,
            'status' => $case->status,
            'parties' => $case->parties,
            'incident_date' => $case->incident_date?->format('Y-m-d'),
            'facts' => $case->facts,
            'metadata' => $case->metadata,
            'evidence_count' => $case->evidence->count(),
            'evidence' => $this->buildEvidenceContext($case->evidence),
            'created_at' => $case->created_at->toIso8601String(),
        ];
    }

    /**
     * Formatear caso para análisis de texto
     */
    public function formatCaseForAnalysis(LegalCase $case): string
    {
        $context = $this->buildCaseContext($case);

        $formatted = "# CASO LEGAL: {$context['title']}\n\n";
        $formatted .= "## Información General\n";
        $formatted .= "- **Tipo de caso:** {$context['type']}\n";
        $formatted .= "- **Estado:** {$context['status']}\n";

        if ($context['incident_date']) {
            $formatted .= "- **Fecha del incidente:** {$context['incident_date']}\n";
        }

        if (!empty($context['parties'])) {
            $formatted .= "\n## Partes Involucradas\n";
            if (isset($context['parties']['plaintiff'])) {
                $formatted .= "- **Demandante:** {$context['parties']['plaintiff']}\n";
            }
            if (isset($context['parties']['defendant'])) {
                $formatted .= "- **Demandado:** {$context['parties']['defendant']}\n";
            }
        }

        $formatted .= "\n## Descripción del Caso\n";
        $formatted .= $context['description'] . "\n";

        if ($context['facts']) {
            $formatted .= "\n## Hechos\n";
            $formatted .= $context['facts'] . "\n";
        }

        if ($context['evidence_count'] > 0) {
            $formatted .= "\n## Evidencia Disponible\n";
            $formatted .= "Total de evidencias: {$context['evidence_count']}\n\n";

            foreach ($context['evidence'] as $evidence) {
                $formatted .= "### {$evidence['title']}\n";
                $formatted .= "- **Tipo:** {$evidence['type']}\n";

                if ($evidence['description']) {
                    $formatted .= "- **Descripción:** {$evidence['description']}\n";
                }

                if ($evidence['is_analyzed'] && !empty($evidence['analysis_result'])) {
                    $formatted .= "- **Análisis:** " . json_encode($evidence['analysis_result']) . "\n";
                }

                $formatted .= "\n";
            }
        }

        return $formatted;
    }

    /**
     * Construir contexto de evidencia
     */
    public function buildEvidenceContext($evidence): array
    {
        if ($evidence instanceof Evidence) {
            $evidence = collect([$evidence]);
        }

        return $evidence->map(function ($item) {
            return [
                'uuid' => $item->uuid,
                'title' => $item->title,
                'description' => $item->description,
                'type' => $item->type,
                'file_url' => $item->file_url,
                'mime_type' => $item->mime_type,
                'is_analyzed' => $item->is_analyzed,
                'analysis_result' => $item->analysis_result,
                'analyzed_at' => $item->analyzed_at?->toIso8601String(),
            ];
        })->toArray();
    }

    /**
     * Formatear evidencia para análisis visual
     */
    public function formatEvidenceForAnalysis(Evidence $evidence, LegalCase $case): string
    {
        $formatted = "# ANÁLISIS DE EVIDENCIA VISUAL\n\n";
        $formatted .= "## Contexto del Caso\n";
        $formatted .= "**Caso:** {$case->title}\n";
        $formatted .= "**Tipo:** {$case->case_type}\n\n";

        $formatted .= "## Evidencia a Analizar\n";
        $formatted .= "**Título:** {$evidence->title}\n";
        $formatted .= "**Tipo:** {$evidence->type}\n";

        if ($evidence->description) {
            $formatted .= "**Descripción:** {$evidence->description}\n";
        }

        $formatted .= "\n## Tarea\n";
        $formatted .= "Analiza esta evidencia visual en el contexto del caso legal. ";
        $formatted .= "Identifica elementos relevantes, personas, objetos, acciones, condiciones del entorno ";
        $formatted .= "y cualquier detalle que pueda ser importante para el análisis legal del caso.\n";

        return $formatted;
    }

    /**
     * Formatear jurisprudencia como contexto
     */
    public function formatJurisprudenceContext(array $precedents): string
    {
        if (empty($precedents)) {
            return "No se encontraron precedentes relevantes.\n";
        }

        $formatted = "# PRECEDENTES LEGALES RELEVANTES\n\n";
        $formatted .= "Total de precedentes encontrados: " . count($precedents) . "\n\n";

        foreach ($precedents as $index => $precedent) {
            $num = $index + 1;

            if ($precedent instanceof Jurisprudence) {
                $formatted .= "## Precedente #{$num}: {$precedent->case_title}\n";
                $formatted .= "- **Número de caso:** {$precedent->case_number}\n";
                $formatted .= "- **Tribunal:** {$precedent->court}\n";
                $formatted .= "- **Fecha de decisión:** {$precedent->decision_date->format('Y-m-d')}\n";
                $formatted .= "- **Nivel de relevancia:** {$precedent->relevance_level}\n";

                if (!empty($precedent->keywords)) {
                    $formatted .= "- **Palabras clave:** " . implode(', ', $precedent->keywords) . "\n";
                }

                $formatted .= "\n**Resumen:**\n{$precedent->summary}\n\n";
                $formatted .= "**Fallo:**\n{$precedent->ruling}\n\n";

                if ($precedent->legal_reasoning) {
                    $formatted .= "**Razonamiento legal:**\n{$precedent->legal_reasoning}\n\n";
                }
            } else {
                // Si es un array
                $formatted .= "## Precedente #{$num}: {$precedent['case_title']}\n";
                $formatted .= "- **Tribunal:** {$precedent['court']}\n";
                $formatted .= "- **Resumen:** {$precedent['summary']}\n\n";
            }

            $formatted .= "---\n\n";
        }

        return $formatted;
    }

    /**
     * Construir prompt para el agente coordinador
     */
    public function buildCoordinatorPrompt(LegalCase $case): string
    {
        $caseContext = $this->formatCaseForAnalysis($case);

        $prompt = "Eres el Agente Coordinador de LEGAL-IA, un sistema de análisis legal asistido por IA.\n\n";
        $prompt .= "Tu tarea es analizar el siguiente caso y coordinar el trabajo de los agentes especializados:\n";
        $prompt .= "- Agente de Jurisprudencia: Busca precedentes relevantes\n";
        $prompt .= "- Agente Visual: Analiza evidencia fotográfica/video\n";
        $prompt .= "- Agente de Argumentos: Genera líneas de defensa/acusación\n\n";

        $prompt .= $caseContext . "\n\n";

        $prompt .= "Proporciona un análisis inicial que incluya:\n";
        $prompt .= "1. Elementos legales clave del caso\n";
        $prompt .= "2. Áreas que requieren investigación de jurisprudencia\n";
        $prompt .= "3. Evidencia que requiere análisis visual\n";
        $prompt .= "4. Posibles líneas argumentales iniciales\n";
        $prompt .= "5. Estrategia de coordinación para los otros agentes\n\n";
        $prompt .= "Responde en formato JSON estructurado.";

        return $prompt;
    }

    /**
     * Construir prompt para búsqueda de jurisprudencia
     */
    public function buildJurisprudenceSearchPrompt(LegalCase $case): string
    {
        $prompt = "Genera una consulta de búsqueda para encontrar precedentes legales relevantes.\n\n";
        $prompt .= "**Caso:** {$case->title}\n";
        $prompt .= "**Tipo:** {$case->case_type}\n";
        $prompt .= "**Descripción:** {$case->description}\n\n";

        if ($case->facts) {
            $prompt .= "**Hechos clave:**\n{$case->facts}\n\n";
        }

        $prompt .= "Genera:\n";
        $prompt .= "1. Una query de búsqueda semántica (1-2 frases)\n";
        $prompt .= "2. 5-7 palabras clave relevantes\n";
        $prompt .= "3. Áreas del derecho aplicables\n\n";
        $prompt .= "Responde en formato JSON: {\"search_query\": \"\", \"keywords\": [], \"legal_areas\": []}";

        return $prompt;
    }

    /**
     * Construir prompt para análisis de argumentos
     */
    public function buildArgumentsPrompt(LegalCase $case, array $precedents = [], array $visualAnalysis = []): string
    {
        $prompt = "Eres el Agente de Argumentos de LEGAL-IA. Genera líneas argumentales para el siguiente caso.\n\n";

        $prompt .= $this->formatCaseForAnalysis($case) . "\n";

        if (!empty($precedents)) {
            $prompt .= "\n" . $this->formatJurisprudenceContext($precedents) . "\n";
        }

        if (!empty($visualAnalysis)) {
            $prompt .= "\n## Análisis de Evidencia Visual\n";
            $prompt .= json_encode($visualAnalysis, JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= "Genera:\n";
        $prompt .= "1. 3-5 líneas de defensa/acusación principales\n";
        $prompt .= "2. Para cada línea: argumentos a favor, en contra, y precedentes aplicables\n";
        $prompt .= "3. Fortalezas y debilidades de cada línea\n";
        $prompt .= "4. Recomendación de la estrategia más prometedora\n";
        $prompt .= "5. Escenarios alternativos de resolución\n\n";
        $prompt .= "Responde en formato JSON estructurado.";

        return $prompt;
    }

    /**
     * Extraer información clave de un caso
     */
    public function extractKeyInformation(LegalCase $case): array
    {
        return [
            'case_type' => $case->case_type,
            'has_visual_evidence' => $case->evidence()->visual()->count() > 0,
            'has_testimonies' => $case->evidence()->byType('testimony')->count() > 0,
            'has_documents' => $case->evidence()->byType('document')->count() > 0,
            'parties_involved' => !empty($case->parties),
            'has_facts' => !empty($case->facts),
            'incident_dated' => !empty($case->incident_date),
        ];
    }
}
