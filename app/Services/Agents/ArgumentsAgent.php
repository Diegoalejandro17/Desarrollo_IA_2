<?php

namespace App\Services\Agents;

use App\Models\LegalCase;
use App\Services\LLMService;
use App\Services\MCPService;

/**
 * Agente de Argumentos
 *
 * Responsable de:
 * - Generar líneas de defensa/acusación
 * - Evaluar fortalezas y debilidades
 * - Proponer escenarios alternativos
 * - Recomendar estrategia legal
 */
class ArgumentsAgent
{
    protected LLMService $llm;
    protected MCPService $mcp;

    public function __construct()
    {
        $this->llm = new LLMService('gemini'); // Gemini GRATIS para argumentación
        $this->mcp = new MCPService();
    }

    /**
     * Ejecutar generación de argumentos
     */
    public function execute(LegalCase $case, array $context = []): array
    {
        $prompt = $this->buildPrompt($case, $context);

        $schema = [
            'defense_lines' => [
                [
                    'title' => 'Título de la línea argumental',
                    'description' => 'Descripción detallada',
                    'strengths' => ['fortaleza1', 'fortaleza2'],
                    'weaknesses' => ['debilidad1', 'debilidad2'],
                    'supporting_precedents' => ['precedente1', 'precedente2'],
                    'probability_of_success' => 'alta|media|baja',
                ]
            ],
            'alternative_scenarios' => [
                [
                    'scenario' => 'Descripción del escenario',
                    'likelihood' => 'alta|media|baja',
                    'implications' => 'Implicaciones legales',
                ]
            ],
            'recommended_strategy' => 'Estrategia recomendada basada en el análisis',
            'key_arguments' => ['argumento1', 'argumento2'],
            'risks' => ['riesgo1', 'riesgo2'],
            'confidence' => 0.85,
        ];

        try {
            $result = $this->llm->analyzeStructured($prompt, $schema);

            return [
                'defense_lines' => $result['defense_lines'] ?? [],
                'alternative_scenarios' => $result['alternative_scenarios'] ?? [],
                'recommended_strategy' => $result['recommended_strategy'] ?? '',
                'key_arguments' => $result['key_arguments'] ?? [],
                'risks' => $result['risks'] ?? [],
                'confidence' => $result['confidence'] ?? 0.7,
            ];

        } catch (\Exception $e) {
            return $this->getFallbackResult($case);
        }
    }

    /**
     * Construir prompt para generación de argumentos
     */
    protected function buildPrompt(LegalCase $case, array $context): string
    {
        $caseContext = $this->mcp->formatCaseForAnalysis($case);

        $prompt = <<<PROMPT
Eres el Agente de Argumentos de LEGAL-IA, especializado en generar estrategias legales y líneas argumentales.

{$caseContext}

PROMPT;

        // Agregar precedentes si están disponibles
        if (!empty($context['precedents'])) {
            $precedentsText = $this->mcp->formatJurisprudenceContext($context['precedents']);
            $prompt .= "\n{$precedentsText}\n";
        }

        // Agregar análisis visual si está disponible
        if (!empty($context['visual_analysis'])) {
            $prompt .= "\n## Análisis de Evidencia Visual\n";
            $prompt .= json_encode($context['visual_analysis'], JSON_PRETTY_PRINT) . "\n\n";
        }

        $prompt .= <<<TASK

## Tarea

Genera un análisis estratégico completo que incluya:

### 1. Líneas de Defensa/Acusación (3-5 líneas)

Para cada línea argumental, proporciona:
- **Título**: Nombre claro de la línea
- **Descripción**: Explicación detallada de la estrategia
- **Fortalezas**: Puntos a favor de esta línea
- **Debilidades**: Puntos en contra o riesgos
- **Precedentes de apoyo**: Qué precedentes respaldan esta línea
- **Probabilidad de éxito**: Alta, media o baja

### 2. Escenarios Alternativos (2-4 escenarios)

Para cada escenario:
- **Descripción**: Cómo podría desarrollarse o resolverse el caso
- **Probabilidad**: Qué tan probable es este escenario
- **Implicaciones**: Consecuencias legales de este escenario

### 3. Estrategia Recomendada

Recomienda la estrategia más prometedora basándote en:
- Fortaleza de la evidencia disponible
- Precedentes aplicables
- Riesgos y beneficios
- Probabilidad de éxito

### 4. Argumentos Clave

Lista los 5-7 argumentos más sólidos que deberían presentarse

### 5. Riesgos

Identifica los principales riesgos o desafíos que podrían surgir

### 6. Nivel de Confianza

Indica tu nivel de confianza en este análisis (0.0 a 1.0)

## Consideraciones

- Basa tus argumentos en hechos y evidencia disponible
- Considera precedentes relevantes
- Mantén un enfoque objetivo y profesional
- Identifica tanto fortalezas como debilidades
- Proporciona opciones estratégicas viables

Responde en formato JSON estructurado siguiendo el esquema proporcionado.
TASK;

        return $prompt;
    }

    /**
     * Resultado de fallback cuando no hay API key
     */
    protected function getFallbackResult(LegalCase $case): array
    {
        return [
            'defense_lines' => [
                [
                    'title' => 'Línea principal de defensa',
                    'description' => 'Basada en la evidencia disponible y el tipo de caso',
                    'strengths' => [
                        'Evidencia disponible',
                        'Tipo de caso establecido',
                    ],
                    'weaknesses' => [
                        'Requiere análisis detallado',
                    ],
                    'supporting_precedents' => [],
                    'probability_of_success' => 'media',
                ],
            ],
            'alternative_scenarios' => [
                [
                    'scenario' => 'Resolución mediante acuerdo',
                    'likelihood' => 'media',
                    'implications' => 'Evitaría proceso judicial prolongado',
                ],
                [
                    'scenario' => 'Juicio completo',
                    'likelihood' => 'media',
                    'implications' => 'Permitiría presentar toda la evidencia',
                ],
            ],
            'recommended_strategy' => 'Realizar análisis detallado de evidencia y precedentes antes de decidir estrategia definitiva. Configure API key para análisis automático completo.',
            'key_arguments' => [
                'Basarse en hechos del caso',
                'Utilizar precedentes relevantes',
                'Presentar evidencia disponible',
            ],
            'risks' => [
                'Falta de análisis automatizado completo',
                'Necesidad de revisión manual por profesional',
            ],
            'confidence' => 0.5,
        ];
    }
}
