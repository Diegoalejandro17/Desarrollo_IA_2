<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jurisprudence;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class JurisprudenceController extends Controller
{
    /**
     * Display a listing of the resource.
     * GET /api/jurisprudence
     */
    public function index(Request $request): JsonResponse
    {
        $query = Jurisprudence::query();

        // Filtros
        if ($request->has('court')) {
            $query->byCourt($request->court);
        }

        if ($request->has('jurisdiction')) {
            $query->byJurisdiction($request->jurisdiction);
        }

        if ($request->has('relevance_level')) {
            $query->where('relevance_level', $request->relevance_level);
        }

        if ($request->has('recent_years')) {
            $query->recent($request->recent_years);
        }

        if ($request->has('keyword')) {
            $query->searchByKeyword($request->keyword);
        }

        // Paginación
        $perPage = $request->get('per_page', 15);
        $jurisprudence = $query->orderBy('decision_date', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $jurisprudence,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * POST /api/jurisprudence
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'case_number' => 'required|string|unique:jurisprudence,case_number',
            'court' => 'required|string',
            'jurisdiction' => 'nullable|string',
            'decision_date' => 'nullable|date',
            'case_title' => 'required|string',
            'summary' => 'required|string',
            'ruling' => 'required|string',
            'legal_reasoning' => 'nullable|string',
            'keywords' => 'nullable|array',
            'articles_cited' => 'nullable|array',
            'url' => 'nullable|url',
            'full_text' => 'nullable|string',
            'relevance_level' => 'nullable|in:high,medium,low',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $jurisprudence = Jurisprudence::create($request->all());

        // TODO: Generar embedding automáticamente
        // dispatch(new GenerateJurisprudenceEmbeddingJob($jurisprudence));

        return response()->json([
            'success' => true,
            'message' => 'Jurisprudencia creada exitosamente',
            'data' => $jurisprudence,
        ], 201);
    }

    /**
     * Display the specified resource.
     * GET /api/jurisprudence/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        $jurisprudence = Jurisprudence::where('uuid', $uuid)->first();

        if (!$jurisprudence) {
            return response()->json([
                'success' => false,
                'message' => 'Jurisprudencia no encontrada',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $jurisprudence,
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT/PATCH /api/jurisprudence/{uuid}
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $jurisprudence = Jurisprudence::where('uuid', $uuid)->first();

        if (!$jurisprudence) {
            return response()->json([
                'success' => false,
                'message' => 'Jurisprudencia no encontrada',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'case_number' => 'sometimes|required|string|unique:jurisprudence,case_number,' . $jurisprudence->id,
            'court' => 'sometimes|required|string',
            'jurisdiction' => 'nullable|string',
            'decision_date' => 'nullable|date',
            'case_title' => 'sometimes|required|string',
            'summary' => 'sometimes|required|string',
            'ruling' => 'sometimes|required|string',
            'legal_reasoning' => 'nullable|string',
            'keywords' => 'nullable|array',
            'articles_cited' => 'nullable|array',
            'url' => 'nullable|url',
            'full_text' => 'nullable|string',
            'relevance_level' => 'nullable|in:high,medium,low',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $jurisprudence->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Jurisprudencia actualizada exitosamente',
            'data' => $jurisprudence,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /api/jurisprudence/{uuid}
     */
    public function destroy(string $uuid): JsonResponse
    {
        $jurisprudence = Jurisprudence::where('uuid', $uuid)->first();

        if (!$jurisprudence) {
            return response()->json([
                'success' => false,
                'message' => 'Jurisprudencia no encontrada',
            ], 404);
        }

        $jurisprudence->delete();

        return response()->json([
            'success' => true,
            'message' => 'Jurisprudencia eliminada exitosamente',
        ]);
    }

    /**
     * Full-text search in jurisprudence
     * POST /api/jurisprudence/search
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:3',
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $searchTerm = $request->query;
        $limit = $request->get('limit', 10);

        // Búsqueda full-text
        $results = Jurisprudence::fullTextSearch($searchTerm)
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'query' => $searchTerm,
            'results_count' => $results->count(),
            'data' => $results,
        ]);
    }

    /**
     * Semantic search using embeddings
     * POST /api/jurisprudence/semantic-search
     */
    public function semanticSearch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string',
            'limit' => 'nullable|integer|min:1|max:50',
            'min_similarity' => 'nullable|numeric|min:0|max:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implementar búsqueda semántica con embeddings
        // 1. Generar embedding de la query usando LLM
        // 2. Buscar jurisprudencia similar usando cosine similarity
        // 3. Retornar resultados ordenados por similitud

        // Por ahora, devolver mensaje de "no implementado"
        return response()->json([
            'success' => false,
            'message' => 'Búsqueda semántica pendiente de implementación con el servicio LLM',
            'note' => 'Se requiere configurar API key de OpenAI/Anthropic para generar embeddings',
        ], 501);
    }

    /**
     * Find similar precedents to a given case description
     * POST /api/jurisprudence/find-similar
     */
    public function findSimilar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'case_description' => 'required|string|min:50',
            'case_type' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // TODO: Implementar con el agente de jurisprudencia
        // Este endpoint será usado por el agente A2A de Jurisprudencia

        return response()->json([
            'success' => false,
            'message' => 'Búsqueda de precedentes similares pendiente de implementación',
            'note' => 'Este método será implementado con el AgentOrchestrator',
        ], 501);
    }

    /**
     * Get jurisprudence statistics
     * GET /api/jurisprudence/stats
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Jurisprudence::count(),
            'by_relevance' => [
                'high' => Jurisprudence::where('relevance_level', 'high')->count(),
                'medium' => Jurisprudence::where('relevance_level', 'medium')->count(),
                'low' => Jurisprudence::where('relevance_level', 'low')->count(),
            ],
            'with_embeddings' => Jurisprudence::whereNotNull('embedding')->count(),
            'recent_5_years' => Jurisprudence::recent(5)->count(),
            'top_courts' => Jurisprudence::select('court')
                ->selectRaw('count(*) as count')
                ->groupBy('court')
                ->orderByDesc('count')
                ->limit(5)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
