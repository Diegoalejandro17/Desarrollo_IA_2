<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class CaseController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/cases
     */
    public function index(Request $request): JsonResponse
    {
        // IMPORTANTE: Solo mostrar casos del usuario autenticado
        $query = LegalCase::with(['evidence', 'latestAnalysis'])
            ->where('user_id', $request->user()->id);

        // Filtros opcionales
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        if ($request->has('case_type')) {
            $query->byType($request->case_type);
        }

        if ($request->has('recent_days')) {
            $query->recent($request->recent_days);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $cases = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $cases,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/cases
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'case_type' => 'required|in:civil,penal,laboral,administrativo,constitucional,otro',
            'parties' => 'nullable|array',
            'parties.plaintiff' => 'nullable|string',
            'parties.defendant' => 'nullable|string',
            'incident_date' => 'nullable|date',
            'facts' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // IMPORTANTE: Asignar caso al usuario autenticado
        $case = LegalCase::create([
            'title' => $request->title,
            'description' => $request->description,
            'case_type' => $request->case_type,
            'status' => 'draft',
            'parties' => $request->parties,
            'incident_date' => $request->incident_date,
            'facts' => $request->facts,
            'metadata' => $request->metadata,
            'user_id' => $request->user()->id, // Usuario autenticado con Sanctum
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Caso legal creado exitosamente',
            'data' => $case->load(['evidence', 'latestAnalysis']),
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/cases/{uuid}
     */
    public function show(string $uuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo mostrar casos del usuario autenticado
        $case = LegalCase::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->with(['evidence', 'analyses' => function ($query) {
                $query->orderBy('version', 'desc');
            }])
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para verlo',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $case,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/cases/{uuid}
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        // IMPORTANTE: Solo actualizar casos del usuario autenticado
        $case = LegalCase::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para editarlo',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'case_type' => 'sometimes|required|in:civil,penal,laboral,administrativo,constitucional,otro',
            'status' => 'sometimes|required|in:draft,analyzing,analyzed,archived',
            'parties' => 'nullable|array',
            'incident_date' => 'nullable|date',
            'facts' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $case->update($request->only([
            'title',
            'description',
            'case_type',
            'status',
            'parties',
            'incident_date',
            'facts',
            'metadata',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Caso actualizado exitosamente',
            'data' => $case->fresh(['evidence', 'latestAnalysis']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/cases/{uuid}
     */
    public function destroy(string $uuid, Request $request): JsonResponse
    {
        // IMPORTANTE: Solo eliminar casos del usuario autenticado
        $case = LegalCase::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$case) {
            return response()->json([
                'success' => false,
                'message' => 'Caso no encontrado o no tienes permiso para eliminarlo',
            ], 404);
        }

        $case->delete();

        return response()->json([
            'success' => true,
            'message' => 'Caso eliminado exitosamente',
        ]);
    }

    /**
     * Get case statistics
     * GET /api/cases/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => LegalCase::count(),
            'by_status' => [
                'draft' => LegalCase::byStatus('draft')->count(),
                'analyzing' => LegalCase::byStatus('analyzing')->count(),
                'analyzed' => LegalCase::byStatus('analyzed')->count(),
                'archived' => LegalCase::byStatus('archived')->count(),
            ],
            'by_type' => [
                'civil' => LegalCase::byType('civil')->count(),
                'penal' => LegalCase::byType('penal')->count(),
                'laboral' => LegalCase::byType('laboral')->count(),
                'administrativo' => LegalCase::byType('administrativo')->count(),
                'constitucional' => LegalCase::byType('constitucional')->count(),
                'otro' => LegalCase::byType('otro')->count(),
            ],
            'recent_30_days' => LegalCase::recent(30)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
