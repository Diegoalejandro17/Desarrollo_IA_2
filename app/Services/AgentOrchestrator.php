<?php

namespace App\Services;

use App\Models\LegalCase;
use App\Models\CaseAnalysis;
use App\Services\Agents\CoordinatorAgent;
use App\Services\Agents\JurisprudenceAgent;
use App\Services\Agents\VisualAnalysisAgent;
use App\Services\Agents\ArgumentsAgent;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Agent-to-Agent Orchestrator
 *
 * Coordina la ejecución de los 4 agentes especializados
 */
class AgentOrchestrator
{
    protected CoordinatorAgent $coordinatorAgent;
    protected JurisprudenceAgent $jurisprudenceAgent;
    protected VisualAnalysisAgent $visualAgent;
    protected ArgumentsAgent $argumentsAgent;
    protected MCPService $mcpService;

    public function __construct()
    {
        $this->coordinatorAgent = new CoordinatorAgent();
        $this->jurisprudenceAgent = new JurisprudenceAgent();
        $this->visualAgent = new VisualAnalysisAgent();
        $this->argumentsAgent = new ArgumentsAgent();
        $this->mcpService = new MCPService();
    }

    /**
     * Orquestar análisis completo de un caso
     */
    public function orchestrateAnalysis(LegalCase $case, CaseAnalysis $analysis): array
    {
        try {
            // Marcar análisis como en proceso
            $analysis->markAsProcessing();
            $analysis->addAgentLog('orchestrator', 'analysis_started', [
                'case_uuid' => $case->uuid,
            ]);

            // PASO 1: Agente Coordinador
            Log::info("Ejecutando Agente Coordinador para caso {$case->uuid}");
            $coordinatorResult = $this->executeAgent('coordinator', $case, $analysis);

            // PASO 2: Agentes paralelos (Jurisprudencia + Visual)
            $jurisprudenceResult = [];
            $visualResult = [];

            // Ejecutar Agente de Jurisprudencia
            try {
                Log::info("Ejecutando Agente de Jurisprudencia");
                $jurisprudenceResult = $this->executeAgent('jurisprudence', $case, $analysis);
            } catch (Exception $e) {
                Log::error("Error en Agente de Jurisprudencia: " . $e->getMessage());
                $analysis->addAgentLog('jurisprudence', 'error', [
                    'message' => $e->getMessage(),
                ]);
            }

            // Ejecutar Agente Visual (solo si hay evidencia visual)
            $hasVisualEvidence = $case->evidence()->visual()->exists();
            if ($hasVisualEvidence) {
                try {
                    Log::info("Ejecutando Agente Visual");
                    $visualResult = $this->executeAgent('visual', $case, $analysis);
                } catch (Exception $e) {
                    Log::error("Error en Agente Visual: " . $e->getMessage());
                    $analysis->addAgentLog('visual', 'error', [
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            // PASO 3: Agente de Argumentos (usa resultados de los anteriores)
            Log::info("Ejecutando Agente de Argumentos");
            $argumentsResult = $this->executeAgent('arguments', $case, $analysis, [
                'precedents' => $jurisprudenceResult['precedents'] ?? [],
                'visual_analysis' => $visualResult['analysis'] ?? [],
            ]);

            // PASO 4: Consolidar resultados
            $consolidatedResults = $this->consolidateResults(
                $coordinatorResult,
                $jurisprudenceResult,
                $visualResult,
                $argumentsResult
            );

            // Actualizar análisis con resultados
            $analysis->update([
                'coordinator_result' => $coordinatorResult,
                'jurisprudence_result' => $jurisprudenceResult,
                'visual_analysis_result' => $visualResult,
                'arguments_result' => $argumentsResult,
                'legal_elements' => $consolidatedResults['legal_elements'],
                'relevant_precedents' => $consolidatedResults['relevant_precedents'],
                'defense_lines' => $consolidatedResults['defense_lines'],
                'alternative_scenarios' => $consolidatedResults['alternative_scenarios'],
                'confidence_scores' => $consolidatedResults['confidence_scores'],
                'executive_summary' => $consolidatedResults['executive_summary'],
            ]);

            // Marcar como completado
            $analysis->markAsCompleted();
            $analysis->addAgentLog('orchestrator', 'analysis_completed', [
                'total_time' => $analysis->processing_time,
            ]);

            // Actualizar estado del caso
            $case->update(['status' => 'analyzed']);

            return $consolidatedResults;

        } catch (Exception $e) {
            Log::error("Error en orquestación: " . $e->getMessage());
            $analysis->markAsFailed($e->getMessage());
            $case->update(['status' => 'draft']);
            throw $e;
        }
    }

    /**
     * Ejecutar un agente específico
     */
    protected function executeAgent(
        string $agentName,
        LegalCase $case,
        CaseAnalysis $analysis,
        array $context = []
    ): array {
        $analysis->addAgentLog($agentName, 'execution_started');

        $startTime = microtime(true);

        try {
            $result = match ($agentName) {
                'coordinator' => $this->coordinatorAgent->execute($case),
                'jurisprudence' => $this->jurisprudenceAgent->execute($case),
                'visual' => $this->visualAgent->execute($case),
                'arguments' => $this->argumentsAgent->execute($case, $context),
                default => throw new Exception("Agente desconocido: {$agentName}"),
            };

            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $analysis->addAgentLog($agentName, 'execution_completed', [
                'execution_time_ms' => $executionTime,
                'result_size' => strlen(json_encode($result)),
            ]);

            return $result;

        } catch (Exception $e) {
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            $analysis->addAgentLog($agentName, 'execution_failed', [
                'execution_time_ms' => $executionTime,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Consolidar resultados de todos los agentes
     */
    protected function consolidateResults(
        array $coordinatorResult,
        array $jurisprudenceResult,
        array $visualResult,
        array $argumentsResult
    ): array {
        // Extraer elementos legales del coordinador
        $legalElements = $coordinatorResult['legal_elements'] ?? [];

        // Extraer precedentes de jurisprudencia
        $relevantPrecedents = $jurisprudenceResult['precedents'] ?? [];

        // Extraer líneas de defensa de argumentos
        $defenseLines = $argumentsResult['defense_lines'] ?? [];

        // Extraer escenarios alternativos
        $alternativeScenarios = $argumentsResult['alternative_scenarios'] ?? [];

        // Calcular scores de confianza
        $confidenceScores = [
            'coordinator' => $coordinatorResult['confidence'] ?? 0.7,
            'jurisprudence' => $jurisprudenceResult['confidence'] ?? 0.8,
            'visual_analysis' => !empty($visualResult) ? ($visualResult['confidence'] ?? 0.75) : null,
            'arguments' => $argumentsResult['confidence'] ?? 0.8,
            'overall' => $this->calculateOverallConfidence([
                $coordinatorResult['confidence'] ?? 0.7,
                $jurisprudenceResult['confidence'] ?? 0.8,
                $visualResult['confidence'] ?? 0.75,
                $argumentsResult['confidence'] ?? 0.8,
            ]),
        ];

        // Generar resumen ejecutivo
        $executiveSummary = $this->generateExecutiveSummary(
            $coordinatorResult,
            $jurisprudenceResult,
            $visualResult,
            $argumentsResult
        );

        return [
            'legal_elements' => $legalElements,
            'relevant_precedents' => $relevantPrecedents,
            'defense_lines' => $defenseLines,
            'alternative_scenarios' => $alternativeScenarios,
            'confidence_scores' => $confidenceScores,
            'executive_summary' => $executiveSummary,
        ];
    }

    /**
     * Calcular confianza general
     */
    protected function calculateOverallConfidence(array $scores): float
    {
        $validScores = array_filter($scores, fn($score) => $score !== null && $score > 0);

        if (empty($validScores)) {
            return 0.0;
        }

        return round(array_sum($validScores) / count($validScores), 2);
    }

    /**
     * Generar resumen ejecutivo
     */
    protected function generateExecutiveSummary(
        array $coordinatorResult,
        array $jurisprudenceResult,
        array $visualResult,
        array $argumentsResult
    ): string {
        $summary = "# RESUMEN EJECUTIVO DEL ANÁLISIS\n\n";

        // Elementos legales clave
        if (!empty($coordinatorResult['legal_elements'])) {
            $summary .= "## Elementos Legales Clave\n";
            foreach ($coordinatorResult['legal_elements'] as $element) {
                $summary .= "- {$element}\n";
            }
            $summary .= "\n";
        }

        // Precedentes relevantes
        $precedentsCount = count($jurisprudenceResult['precedents'] ?? []);
        if ($precedentsCount > 0) {
            $summary .= "## Precedentes Legales\n";
            $summary .= "Se identificaron {$precedentsCount} precedentes relevantes que respaldan diferentes líneas argumentales.\n\n";
        }

        // Evidencia visual
        if (!empty($visualResult)) {
            $summary .= "## Evidencia Visual\n";
            $summary .= "El análisis de evidencia visual revela elementos importantes para el caso.\n\n";
        }

        // Líneas de defensa
        $defenseLinesCount = count($argumentsResult['defense_lines'] ?? []);
        if ($defenseLinesCount > 0) {
            $summary .= "## Estrategia Recomendada\n";
            $summary .= "Se han identificado {$defenseLinesCount} posibles líneas argumentales. ";
            $summary .= "La estrategia más prometedora se detalla en el análisis completo.\n\n";
        }

        // Recomendación final
        if (!empty($argumentsResult['recommended_strategy'])) {
            $summary .= "## Recomendación Final\n";
            $summary .= $argumentsResult['recommended_strategy'] . "\n";
        }

        return $summary;
    }

    /**
     * Obtener estadísticas de ejecución
     */
    public function getExecutionStats(CaseAnalysis $analysis): array
    {
        $logs = $analysis->agent_execution_log ?? [];

        $agentStats = [];
        foreach ($logs as $log) {
            $agent = $log['agent'] ?? 'unknown';

            if (!isset($agentStats[$agent])) {
                $agentStats[$agent] = [
                    'executions' => 0,
                    'total_time_ms' => 0,
                    'errors' => 0,
                ];
            }

            $agentStats[$agent]['executions']++;

            if (isset($log['data']['execution_time_ms'])) {
                $agentStats[$agent]['total_time_ms'] += $log['data']['execution_time_ms'];
            }

            if ($log['event'] === 'execution_failed' || $log['event'] === 'error') {
                $agentStats[$agent]['errors']++;
            }
        }

        return $agentStats;
    }
}
