<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use App\Models\Evidence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class EvidenceController extends Controller
{
    /**
     * Get all evidence for a specific case
     * GET /api/cases/{uuid}/evidence
     */
    public function index(string $caseUuid, Request $request): JsonResponse
    {
        $case = LegalCase::where('uuid', $caseUuid)->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado',
            ], 404);
        }

        $query = Evidence::where('legal_case_id', $case->id);

        // Filtros
        if ($request->has('type')) {
            $query->byType($request->type);
        }

        if ($request->has('is_analyzed')) {
            $analyzed = filter_var($request->is_analyzed, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_analyzed', $analyzed);
        }

        $evidence = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $evidence,
        ]);
    }

    /**
     * Upload new evidence for a case
     * POST /api/cases/{uuid}/evidence
     */
    public function store(string $caseUuid, Request $request): JsonResponse
    {
        $case = LegalCase::where('uuid', $caseUuid)->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:document,image,video,audio,testimony,other',
            'file' => 'required_without:file_url|file|max:51200', // Max 50MB
            'file_url' => 'required_without:file|url', // URL de Supabase Storage si ya está subido
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $evidenceData = [
            'legal_case_id' => $case->id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
        ];

        // Si viene un archivo, guardarlo
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();

            // Guardar en storage/app/public/evidence
            $path = $file->storeAs('evidence', $filename, 'public');

            $evidenceData['file_path'] = $path;
            $evidenceData['file_url'] = Storage::url($path);
            $evidenceData['mime_type'] = $file->getMimeType();
            $evidenceData['file_size'] = $file->getSize();
        } elseif ($request->file_url) {
            // Si viene URL de Supabase Storage
            $evidenceData['file_url'] = $request->file_url;
            $evidenceData['mime_type'] = $request->mime_type ?? null;
            $evidenceData['file_size'] = $request->file_size ?? null;
        }

        $evidence = Evidence::create($evidenceData);

        // TODO: Si es evidencia visual, disparar análisis automático
        // if ($evidence->isVisual()) {
        //     dispatch(new AnalyzeVisualEvidenceJob($evidence));
        // }

        return response()->json([
            'success' => true,
            'message' => 'Evidencia cargada exitosamente',
            'data' => $evidence,
        ], 201);
    }

    /**
     * Get specific evidence
     * GET /api/evidence/{uuid}
     */
    public function show(string $evidenceUuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $evidenceUuid)
            ->with('legalCase')
            ->first();

        if (!$evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $evidence,
        ]);
    }

    /**
     * Update evidence metadata
     * PUT/PATCH /api/evidence/{uuid}
     */
    public function update(string $evidenceUuid, Request $request): JsonResponse
    {
        $evidence = Evidence::where('uuid', $evidenceUuid)->first();

        if (!$evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $evidence->update($request->only(['title', 'description', 'metadata']));

        return response()->json([
            'success' => true,
            'message' => 'Evidencia actualizada exitosamente',
            'data' => $evidence,
        ]);
    }

    /**
     * Delete evidence
     * DELETE /api/evidence/{uuid}
     */
    public function destroy(string $evidenceUuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $evidenceUuid)->first();

        if (!$evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada',
            ], 404);
        }

        // Eliminar archivo físico si existe
        if ($evidence->file_path && Storage::disk('public')->exists($evidence->file_path)) {
            Storage::disk('public')->delete($evidence->file_path);
        }

        $evidence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Evidencia eliminada exitosamente',
        ]);
    }

    /**
     * Trigger analysis for visual evidence
     * POST /api/evidence/{uuid}/analyze
     */
    public function analyzeVisual(string $evidenceUuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $evidenceUuid)->first();

        if (!$evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada',
            ], 404);
        }

        if (!$evidence->isVisual()) {
            return response()->json([
                'success' => false,
                'message' => 'Solo se puede analizar evidencia de tipo imagen o video',
                'current_type' => $evidence->type,
            ], 422);
        }

        if ($evidence->is_analyzed) {
            return response()->json([
                'success' => false,
                'message' => 'Esta evidencia ya ha sido analizada',
                'analyzed_at' => $evidence->analyzed_at,
            ], 422);
        }

        // TODO: Disparar análisis con el agente visual
        // dispatch(new AnalyzeVisualEvidenceJob($evidence));

        return response()->json([
            'success' => true,
            'message' => 'Análisis de evidencia visual iniciado',
            'data' => $evidence,
        ], 202);
    }

    /**
     * Get analysis result for evidence
     * GET /api/evidence/{uuid}/analysis
     */
    public function getAnalysis(string $evidenceUuid): JsonResponse
    {
        $evidence = Evidence::where('uuid', $evidenceUuid)->first();

        if (!$evidence) {
            return response()->json([
                'success' => false,
                'message' => 'Evidencia no encontrada',
            ], 404);
        }

        if (!$evidence->is_analyzed) {
            return response()->json([
                'success' => false,
                'message' => 'Esta evidencia aún no ha sido analizada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'evidence' => $evidence,
                'analysis_result' => $evidence->analysis_result,
                'analyzed_at' => $evidence->analyzed_at,
            ],
        ]);
    }

    /**
     * Bulk upload evidence
     * POST /api/cases/{uuid}/evidence/bulk
     */
    public function bulkUpload(string $caseUuid, Request $request): JsonResponse
    {
        $case = LegalCase::where('uuid', $caseUuid)->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'files' => 'required|array|min:1|max:10',
            'files.*' => 'required|file|max:51200',
            'type' => 'required|in:document,image,video,audio,testimony,other',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $uploadedEvidence = [];
        $errors = [];

        foreach ($request->file('files') as $index => $file) {
            try {
                $filename = time() . '_' . $index . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('evidence', $filename, 'public');

                $evidence = Evidence::create([
                    'legal_case_id' => $case->id,
                    'title' => $file->getClientOriginalName(),
                    'type' => $request->type,
                    'file_path' => $path,
                    'file_url' => Storage::url($path),
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);

                $uploadedEvidence[] = $evidence;
            } catch (\Exception $e) {
                $errors[] = [
                    'file' => $file->getClientOriginalName(),
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => count($errors) === 0,
            'message' => count($uploadedEvidence) . ' archivos cargados exitosamente',
            'data' => $uploadedEvidence,
            'errors' => $errors,
        ], count($errors) > 0 ? 207 : 201); // 207 Multi-Status si hay errores parciales
    }
}
