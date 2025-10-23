<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\CaseAnalysis;
use App\Services\AgentOrchestrator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AnalysisController extends Controller
{
    /**
     * Trigger analysis for a legal case
     * POST /api/cases/{uuid}/analyze
     */
    public function analyze(string $caseUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo analizar casos del usuario autenticado
        $case = LegalCase::where('uuid', $caseUuid)
            ->where('user_id', $request->user()->id)
            ->with('evidence')
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para analizarlo',
            ], 404);
        }

        if (!$case->canBeAnalyzed()) {
            return response()->json([
                'success' => false,
                'message' => 'El caso no puede ser analizado en su estado actual',
                'current_status' => $case->status,
            ], 422);
        }

        // Obtener la versión más reciente
        $latestVersion = CaseAnalysis::where('legal_case_id', $case->id)
            ->max('version') ?? 0;

        // Crear nuevo análisis
        $analysis = CaseAnalysis::create([
            'legal_case_id' => $case->id,
            'status' => 'pending',
            'version' => $latestVersion + 1,
        ]);

        // Actualizar estado del caso
        $case->update(['status' => 'analyzing']);

        try {
            // Ejecutar análisis con el orquestador de agentes
            $orchestrator = new AgentOrchestrator();
            $results = $orchestrator->orchestrateAnalysis($case, $analysis);

            return response()->json([
                'success' => true,
                'message' => 'Análisis completado exitosamente',
                'data' => [
                    'case' => $case->fresh(),
                    'analysis' => $analysis->fresh(),
                    'results' => $results,
                ],
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error en análisis: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error durante el análisis: ' . $e->getMessage(),
                'data' => [
                    'case' => $case,
                    'analysis' => $analysis,
                ],
            ], 500);
        }
    }

    /**
     * Get analysis results for a case
     * GET /api/cases/{uuid}/analysis
     */
    public function getAnalysis(string $caseUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo ver análisis de casos del usuario autenticado
        $case = LegalCase::where('uuid', $caseUuid)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para ver su análisis',
            ], 404);
        }

        $query = CaseAnalysis::where('legal_case_id', $case->id);

        // Filtrar por versión específica
        if ($request->has('version')) {
            $query->where('version', $request->version);
        }

        // Filtrar por estado
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $analyses = $query->orderBy('version', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $analyses,
        ]);
    }

    /**
     * Get specific analysis by UUID
     * GET /api/analysis/{uuid}
     */
    public function show(string $analysisUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo ver análisis de casos del usuario autenticado
        $analysis = CaseAnalysis::where('uuid', $analysisUuid)
            ->with('legalCase')
            ->whereHas('legalCase', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'Análisis no encontrado o no tienes permiso para verlo',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Get latest completed analysis for a case
     * GET /api/cases/{uuid}/analysis/latest
     */
    public function getLatestAnalysis(string $caseUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo ver análisis de casos del usuario autenticado
        $case = LegalCase::where('uuid', $caseUuid)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para ver su análisis',
            ], 404);
        }

        $analysis = CaseAnalysis::where('legal_case_id', $case->id)
            ->completed()
            ->latestVersion()
            ->first();

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'No hay análisis completados para este caso',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $analysis,
        ]);
    }

    /**
     * Re-analyze a case (create new version)
     * POST /api/cases/{uuid}/re-analyze
     */
    public function reanalyze(string $caseUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo reanalizar casos del usuario autenticado
        $case = LegalCase::where('uuid', $caseUuid)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para reanalizarlo',
            ], 404);
        }

        // Obtener análisis previo
        $previousAnalysis = CaseAnalysis::where('legal_case_id', $case->id)
            ->latestVersion()
            ->first();

        $newVersion = $previousAnalysis ? $previousAnalysis->version + 1 : 1;

        // Crear nuevo análisis vinculado al anterior
        $analysis = CaseAnalysis::create([
            'legal_case_id' => $case->id,
            'status' => 'pending',
            'version' => $newVersion,
            'previous_analysis_id' => $previousAnalysis?->id,
        ]);

        // Actualizar estado del caso
        $case->update(['status' => 'analyzing']);

        // TODO: Disparar proceso de análisis
        // dispatch(new AnalyzeCaseJob($case, $analysis));

        return response()->json([
            'success' => true,
            'message' => 'Re-análisis iniciado exitosamente',
            'data' => [
                'case' => $case,
                'analysis' => $analysis,
                'previous_version' => $previousAnalysis?->version,
            ],
        ], 202);
    }

    /**
     * Cancel ongoing analysis
     * POST /api/analysis/{uuid}/cancel
     */
    public function cancel(string $analysisUuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo cancelar análisis de casos del usuario autenticado
        $analysis = CaseAnalysis::where('uuid', $analysisUuid)
            ->whereHas('legalCase', function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->first();

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'Análisis no encontrado o no tienes permiso para cancelarlo',
            ], 404);
        }

        if (!$analysis->isProcessing()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden cancelar análisis en proceso',
                'current_status' => $analysis->status,
            ], 422);
        }

        $analysis->markAsFailed('Cancelado por el usuario');

        // Actualizar estado del caso
        $analysis->legalCase->update(['status' => 'draft']);

        return response()->json([
            'success' => true,
            'message' => 'Análisis cancelado exitosamente',
            'data' => $analysis,
        ]);
    }

    /**
     * Get analysis statistics
     * GET /api/analysis/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => CaseAnalysis::count(),
            'by_status' => [
                'pending' => CaseAnalysis::pending()->count(),
                'processing' => CaseAnalysis::processing()->count(),
                'completed' => CaseAnalysis::completed()->count(),
                'failed' => CaseAnalysis::failed()->count(),
            ],
            'average_processing_time' => CaseAnalysis::completed()
                ->whereNotNull('processing_time')
                ->avg('processing_time'),
            'high_confidence_analyses' => CaseAnalysis::completed()
                ->get()
                ->filter(fn($analysis) => $analysis->hasHighConfidence())
                ->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
