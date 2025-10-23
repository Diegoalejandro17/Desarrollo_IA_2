<?php

namespace App\Services\Agents;

use App\Models\LegalCase;
use App\Models\Evidence;
use App\Services\LLMService;
use App\Services\MCPService;

/**
 * Agente de Análisis Visual
 *
 * Responsable de:
 * - Análisis de imágenes con GPT-4 Vision
 * - Extracción de elementos relevantes de evidencia visual
 * - Interpretación contextual en el marco legal
 */
class VisualAnalysisAgent
{
    protected LLMService $llm;
    protected MCPService $mcp;

    public function __construct()
    {
        $this->llm = new LLMService('gemini'); // Gemini Vision GRATIS
        $this->mcp = new MCPService();
    }

    /**
     * Ejecutar análisis visual
     */
    public function execute(LegalCase $case): array
    {
        // Obtener evidencia visual del caso
        $visualEvidence = $case->evidence()->visual()->get();

        if ($visualEvidence->isEmpty()) {
            return [
                'analysis' => [],
                'summary' => 'No hay evidencia visual para analizar',
                'confidence' => 0.0,
            ];
        }

        $analyses = [];

        foreach ($visualEvidence as $evidence) {
            $analysis = $this->analyzeEvidence($evidence, $case);
            $analyses[] = $analysis;

            // Guardar análisis en la evidencia
            $evidence->markAsAnalyzed($analysis);
        }

        return [
            'analysis' => $analyses,
            'summary' => $this->generateSummary($analyses),
            'key_findings' => $this->extractKeyFindings($analyses),
            'confidence' => $this->calculateConfidence($analyses),
        ];
    }

    /**
     * Analizar una evidencia visual específica
     */
    protected function analyzeEvidence(Evidence $evidence, LegalCase $case): array
    {
        if (!$evidence->file_url) {
            return [
                'evidence_uuid' => $evidence->uuid,
                'status' => 'error',
                'message' => 'No se encontró URL de archivo',
            ];
        }

        $prompt = $this->buildAnalysisPrompt($evidence, $case);

        try {
            $analysisText = $this->llm->analyzeImage($evidence->file_url, $prompt);

            return [
                'evidence_uuid' => $evidence->uuid,
                'evidence_title' => $evidence->title,
                'type' => $evidence->type,
                'status' => 'success',
                'analysis' => $analysisText,
                'key_elements' => $this->extractElements($analysisText),
                'legal_relevance' => $this->assessLegalRelevance($analysisText, $case),
                'confidence' => 0.85,
            ];

        } catch (\Exception $e) {
            return [
                'evidence_uuid' => $evidence->uuid,
                'status' => 'error',
                'message' => $e->getMessage(),
                'fallback_analysis' => $this->getFallbackAnalysis($evidence, $case),
            ];
        }
    }

    /**
     * Construir prompt para análisis de imagen
     */
    protected function buildAnalysisPrompt(Evidence $evidence, LegalCase $case): string
    {
        $contextPrompt = $this->mcp->formatEvidenceForAnalysis($evidence, $case);

        $prompt = <<<PROMPT
{$contextPrompt}

## Instrucciones de Análisis

Analiza detalladamente esta evidencia visual y proporciona:

1. **Descripción General**: Describe lo que se observa en la imagen/video

2. **Elementos Clave**: Identifica:
   - Personas presentes y sus acciones
   - Objetos relevantes (vehículos, señales, armas, documentos, etc.)
   - Condiciones del entorno (iluminación, clima, estado de la vía, etc.)
   - Marcas temporales o espaciales visibles

3. **Detalles Específicos**: Observa:
   - Estado de daños visibles
   - Posiciones relativas de elementos
   - Señales de tráfico, semáforos u otros indicadores
   - Cualquier texto visible (matrículas, letreros, etc.)

4. **Relevancia Legal**: Evalúa qué elementos son particularmente relevantes para el caso legal

5. **Inconsistencias o Particularidades**: Identifica cualquier elemento que llame la atención

6. **Limitaciones**: Menciona qué no se puede determinar con certeza desde la evidencia

Proporciona un análisis objetivo, profesional y detallado.
PROMPT;

        return $prompt;
    }

    /**
     * Extraer elementos clave del análisis
     */
    protected function extractElements(string $analysisText): array
    {
        $elements = [];

        // Buscar patrones comunes
        if (preg_match_all('/(?:persona|individuo|conductor|peatón)/i', $analysisText, $matches)) {
            $elements['people'] = count($matches[0]);
        }

        if (preg_match_all('/(?:vehículo|automóvil|coche|moto)/i', $analysisText, $matches)) {
            $elements['vehicles'] = count($matches[0]);
        }

        if (preg_match_all('/(?:semáforo|señal|indicador)/i', $analysisText, $matches)) {
            $elements['traffic_signs'] = count($matches[0]);
        }

        if (preg_match('/(?:daño|daños|colisión|impacto)/i', $analysisText)) {
            $elements['damage'] = true;
        }

        return $elements;
    }

    /**
     * Evaluar relevancia legal
     */
    protected function assessLegalRelevance(string $analysisText, LegalCase $case): string
    {
        $keywords = [
            'civil' => ['daños', 'negligencia', 'responsabilidad', 'accidente'],
            'penal' => ['delito', 'evidencia', 'sospechoso', 'arma'],
            'laboral' => ['condiciones', 'seguridad', 'accidente laboral'],
        ];

        $caseKeywords = $keywords[$case->case_type] ?? [];
        $matchCount = 0;

        foreach ($caseKeywords as $keyword) {
            if (stripos($analysisText, $keyword) !== false) {
                $matchCount++;
            }
        }

        if ($matchCount >= 3) {
            return 'alta';
        } elseif ($matchCount >= 1) {
            return 'media';
        } else {
            return 'baja';
        }
    }

    /**
     * Generar resumen de todos los análisis
     */
    protected function generateSummary(array $analyses): string
    {
        $successful = array_filter($analyses, fn($a) => $a['status'] === 'success');
        $count = count($successful);

        if ($count === 0) {
            return 'No se pudo analizar la evidencia visual';
        }

        $summary = "Se analizaron {$count} evidencias visuales. ";

        $highRelevance = array_filter($successful, fn($a) => ($a['legal_relevance'] ?? '') === 'alta');
        if (count($highRelevance) > 0) {
            $summary .= count($highRelevance) . " presenta(n) alta relevancia legal. ";
        }

        return $summary . "Los hallazgos clave se detallan en el análisis completo.";
    }

    /**
     * Extraer hallazgos clave de todos los análisis
     */
    protected function extractKeyFindings(array $analyses): array
    {
        $findings = [];

        foreach ($analyses as $analysis) {
            if ($analysis['status'] === 'success') {
                if (!empty($analysis['key_elements'])) {
                    $findings[] = [
                        'evidence' => $analysis['evidence_title'],
                        'elements' => $analysis['key_elements'],
                        'relevance' => $analysis['legal_relevance'] ?? 'media',
                    ];
                }
            }
        }

        return $findings;
    }

    /**
     * Calcular confianza del análisis
     */
    protected function calculateConfidence(array $analyses): float
    {
        $successful = array_filter($analyses, fn($a) => $a['status'] === 'success');

        if (empty($successful)) {
            return 0.0;
        }

        $avgConfidence = array_sum(array_column($successful, 'confidence')) / count($successful);
        $successRate = count($successful) / count($analyses);

        return round(($avgConfidence * 0.7) + ($successRate * 0.3), 2);
    }

    /**
     * Análisis de fallback cuando no hay API key
     */
    protected function getFallbackAnalysis(Evidence $evidence, LegalCase $case): array
    {
        return [
            'evidence_uuid' => $evidence->uuid,
            'status' => 'fallback',
            'analysis' => "Evidencia {$evidence->type} identificada. Requiere análisis manual por profesional legal.",
            'note' => 'API key de OpenAI no configurada. Configure OPENAI_API_KEY para análisis automático.',
        ];
    }
}
