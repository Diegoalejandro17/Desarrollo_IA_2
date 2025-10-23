<?php

namespace App\Services\Agents;

use App\Models\LegalCase;
use App\Services\LLMService;
use App\Services\MCPService;

/**
 * Agente Coordinador
 *
 * Responsable de:
 * - Análisis inicial del caso
 * - Identificación de elementos legales clave
 * - Coordinación de la estrategia de análisis
 */
class CoordinatorAgent
{
    protected LLMService $llm;
    protected MCPService $mcp;

    public function __construct()
    {
        $this->llm = new LLMService('gemini'); // Usar Gemini GRATIS para coordinación
        $this->mcp = new MCPService();
    }

    /**
     * Ejecutar análisis de coordinación
     */
    public function execute(LegalCase $case): array
    {
        $prompt = $this->buildPrompt($case);

        $schema = [
            'legal_elements' => ['elemento1', 'elemento2'],
            'case_classification' => 'clasificación del caso',
            'complexity_level' => 'bajo|medio|alto',
            'recommended_approach' => 'descripción de la estrategia',
            'jurisprudence_search_areas' => ['área1', 'área2'],
            'visual_evidence_priority' => 'alta|media|baja',
            'estimated_strength' => 'débil|moderado|fuerte',
            'key_challenges' => ['desafío1', 'desafío2'],
            'confidence' => 0.85,
        ];

        try {
            $result = $this->llm->analyzeStructured($prompt, $schema);

            return [
                'legal_elements' => $result['legal_elements'] ?? [],
                'case_classification' => $result['case_classification'] ?? '',
                'complexity_level' => $result['complexity_level'] ?? 'medio',
                'recommended_approach' => $result['recommended_approach'] ?? '',
                'jurisprudence_search_areas' => $result['jurisprudence_search_areas'] ?? [],
                'visual_evidence_priority' => $result['visual_evidence_priority'] ?? 'media',
                'estimated_strength' => $result['estimated_strength'] ?? 'moderado',
                'key_challenges' => $result['key_challenges'] ?? [],
                'confidence' => $result['confidence'] ?? 0.7,
            ];

        } catch (\Exception $e) {
            // Fallback si no hay API key configurada
            return $this->getFallbackResult($case);
        }
    }

    /**
     * Construir prompt para el coordinador
     */
    protected function buildPrompt(LegalCase $case): string
    {
        $caseContext = $this->mcp->formatCaseForAnalysis($case);

        $prompt = <<<PROMPT
Eres el Agente Coordinador de LEGAL-IA, un sistema especializado en análisis legal asistido por IA.

Tu tarea es realizar un análisis inicial del caso y coordinar la estrategia de análisis.

{$caseContext}

## Análisis Requerido

1. **Elementos Legales Clave**: Identifica los elementos legales fundamentales del caso (responsabilidad, negligencia, causalidad, daños, etc.)

2. **Clasificación del Caso**: Categoriza el caso según su naturaleza y complejidad

3. **Nivel de Complejidad**: Evalúa si el caso es de complejidad baja, media o alta

4. **Estrategia Recomendada**: Propón un enfoque general para el análisis del caso

5. **Áreas de Búsqueda de Jurisprudencia**: Identifica qué áreas del derecho y qué tipos de precedentes deberían buscarse

6. **Prioridad de Evidencia Visual**: Si hay evidencia visual, evalúa su importancia (alta, media, baja)

7. **Fortaleza Estimada**: Evalúa preliminarmente la fortaleza del caso (débil, moderado, fuerte)

8. **Desafíos Clave**: Identifica los principales desafíos o puntos débiles del caso

9. **Nivel de Confianza**: Indica tu nivel de confianza en este análisis (0.0 a 1.0)

Proporciona un análisis profesional, objetivo y detallado que sirva como base para el trabajo de los agentes especializados.
PROMPT;

        return $prompt;
    }

    /**
     * Resultado de fallback cuando no hay API key
     */
    protected function getFallbackResult(LegalCase $case): array
    {
        return [
            'legal_elements' => [
                'Responsabilidad civil',
                'Causalidad',
                'Daños y perjuicios',
            ],
            'case_classification' => 'Caso ' . $case->case_type . ' estándar',
            'complexity_level' => 'medio',
            'recommended_approach' => 'Análisis integral con enfoque en precedentes y evidencia disponible',
            'jurisprudence_search_areas' => [
                'Responsabilidad civil',
                'Accidentes de tránsito',
            ],
            'visual_evidence_priority' => $case->evidence()->visual()->exists() ? 'alta' : 'baja',
            'estimated_strength' => 'moderado',
            'key_challenges' => [
                'Testimonios contradictorios',
                'Necesidad de establecer causalidad',
            ],
            'confidence' => 0.6,
        ];
    }
}
