<?php

namespace App\Services\Agents;

use App\Models\LegalCase;
use App\Models\Jurisprudence;
use App\Services\LLMService;
use App\Services\MCPService;
use App\Services\MCPWebSearchService;

/**
 * Agente de Jurisprudencia
 *
 * Responsable de:
 * - Búsqueda semántica de precedentes en BD local
 * - Búsqueda web usando MCP Client (si está disponible)
 * - Identificación de jurisprudencia relevante
 * - Ranking de precedentes por similitud
 */
class JurisprudenceAgent
{
    protected LLMService $llm;
    protected MCPService $mcp;
    protected ?MCPWebSearchService $mcpWebSearch = null;

    public function __construct()
    {
        $this->llm = new LLMService('gemini'); // Gemini GRATIS para embeddings
        $this->mcp = new MCPService();

        // Inicializar MCP Web Search (opcional, si está configurado)
        try {
            $this->mcpWebSearch = new MCPWebSearchService();
        } catch (\Exception $e) {
            $this->mcpWebSearch = null;
        }
    }

    /**
     * Ejecutar búsqueda de jurisprudencia
     */
    public function execute(LegalCase $case): array
    {
        // Generar query de búsqueda semántica
        $searchQuery = $this->generateSearchQuery($case);

        // Buscar precedentes usando embeddings en BD local
        $localPrecedents = $this->searchPrecedents($searchQuery, $case);

        // NUEVO: Buscar también en internet usando MCP Client
        $webResults = $this->searchWebJurisprudence($searchQuery, $case);

        // Combinar resultados locales y web
        $allPrecedents = array_merge($localPrecedents, $webResults);

        // Analizar relevancia de cada precedente
        $analyzedPrecedents = $this->analyzePrecedents($allPrecedents, $case);

        return [
            'search_query' => $searchQuery,
            'precedents' => $analyzedPrecedents,
            'total_found' => count($analyzedPrecedents),
            'local_results' => count($localPrecedents),
            'web_results' => count($webResults),
            'mcp_client_used' => $this->mcpWebSearch && $this->mcpWebSearch->isAvailable(),
            'confidence' => $this->calculateConfidence($analyzedPrecedents),
        ];
    }

    /**
     * Generar query de búsqueda optimizada
     */
    protected function generateSearchQuery(LegalCase $case): string
    {
        $prompt = $this->mcp->buildJurisprudenceSearchPrompt($case);

        try {
            $result = $this->llm->analyzeStructured($prompt);
            return $result['search_query'] ?? $this->getDefaultQuery($case);
        } catch (\Exception $e) {
            return $this->getDefaultQuery($case);
        }
    }

    /**
     * Buscar precedentes usando embeddings semánticos
     */
    protected function searchPrecedents(string $query, LegalCase $case): array
    {
        try {
            // Generar embedding de la query
            $queryEmbedding = $this->llm->generateEmbedding($query);

            // Buscar en jurisprudencia con embeddings
            $allJurisprudence = Jurisprudence::whereNotNull('embedding')
                ->limit(100) // Limitar para eficiencia
                ->get();

            if ($allJurisprudence->isEmpty()) {
                // Si no hay jurisprudencia con embeddings, usar búsqueda por keywords
                return $this->fallbackKeywordSearch($case);
            }

            // Calcular similitud coseno con cada precedente
            $precedentsWithScore = [];

            foreach ($allJurisprudence as $jurisprudence) {
                $similarity = $jurisprudence->cosineSimilarity($queryEmbedding);

                if ($similarity > 0.7) { // Threshold de similitud
                    $precedentsWithScore[] = [
                        'jurisprudence' => $jurisprudence,
                        'similarity_score' => $similarity,
                    ];
                }
            }

            // Ordenar por similitud descendente
            usort($precedentsWithScore, function ($a, $b) {
                return $b['similarity_score'] <=> $a['similarity_score'];
            });

            // Retornar top 10
            return array_slice($precedentsWithScore, 0, 10);

        } catch (\Exception $e) {
            // Fallback a búsqueda tradicional
            return $this->fallbackKeywordSearch($case);
        }
    }

    /**
     * Analizar relevancia de precedentes encontrados
     */
    protected function analyzePrecedents(array $precedents, LegalCase $case): array
    {
        $analyzed = [];

        foreach ($precedents as $precedentData) {
            $jurisprudence = $precedentData['jurisprudence'] ?? $precedentData;
            $similarityScore = $precedentData['similarity_score'] ?? 0.75;

            // Convertir a array para manejar tanto Jurisprudence como stdClass
            $jData = $jurisprudence instanceof Jurisprudence
                ? $jurisprudence->toArray()
                : (array) $jurisprudence;

            $analyzed[] = [
                'uuid' => $jData['uuid'] ?? null,
                'case_number' => $jData['case_number'] ?? 'N/A',
                'case_title' => $jData['case_title'] ?? $jData['title'] ?? 'Sin título',
                'court' => $jData['court'] ?? 'Desconocido',
                'decision_date' => isset($jData['decision_date'])
                    ? (is_string($jData['decision_date']) ? $jData['decision_date'] : $jData['decision_date']->format('Y-m-d'))
                    : null,
                'summary' => $jData['summary'] ?? $jData['description'] ?? '',
                'ruling' => $jData['ruling'] ?? '',
                'legal_reasoning' => $jData['legal_reasoning'] ?? '',
                'keywords' => $jData['keywords'] ?? [],
                'articles_cited' => $jData['articles_cited'] ?? [],
                'relevance_level' => $jData['relevance_level'] ?? 'medium',
                'similarity_score' => round($similarityScore, 3),
                'relevance_explanation' => $this->explainRelevance($jurisprudence, $case),
            ];
        }

        return $analyzed;
    }

    /**
     * Explicar por qué un precedente es relevante
     */
    protected function explainRelevance(Jurisprudence|\stdClass $jurisprudence, LegalCase $case): string
    {
        $reasons = [];

        // Convertir a array para manejar ambos tipos
        $jData = $jurisprudence instanceof Jurisprudence
            ? $jurisprudence->toArray()
            : (array) $jurisprudence;

        $caseTitle = $jData['case_title'] ?? $jData['title'] ?? '';
        $keywords = $jData['keywords'] ?? [];
        $relevanceLevel = $jData['relevance_level'] ?? 'medium';

        // Mismo tipo de caso
        if ($caseTitle && str_contains(strtolower($caseTitle), strtolower($case->case_type))) {
            $reasons[] = "Mismo tipo de caso ({$case->case_type})";
        }

        // Keywords coincidentes
        if ($case->description && !empty($keywords)) {
            $caseWords = explode(' ', strtolower($case->description));
            $keywordArray = is_array($keywords) ? $keywords : json_decode($keywords, true) ?? [];
            $matchingKeywords = array_intersect(
                array_map('strtolower', $keywordArray),
                $caseWords
            );

            if (!empty($matchingKeywords)) {
                $reasons[] = "Palabras clave coincidentes: " . implode(', ', array_slice($matchingKeywords, 0, 3));
            }
        }

        // Alta relevancia
        if ($relevanceLevel === 'high') {
            $reasons[] = "Precedente de alta relevancia";
        }

        return !empty($reasons) ? implode('; ', $reasons) : "Similitud temática con el caso";
    }

    /**
     * Búsqueda de fallback por keywords
     */
    protected function fallbackKeywordSearch(LegalCase $case): array
    {
        $keywords = $this->extractKeywords($case);

        $query = Jurisprudence::query();

        foreach ($keywords as $keyword) {
            $query->orWhere('case_title', 'like', "%{$keyword}%")
                ->orWhere('summary', 'like', "%{$keyword}%")
                ->orWhereJsonContains('keywords', $keyword);
        }

        $results = $query->limit(10)->get();

        return $results->map(function ($jurisprudence) {
            return [
                'jurisprudence' => $jurisprudence,
                'similarity_score' => 0.75, // Score por defecto
            ];
        })->toArray();
    }

    /**
     * Extraer keywords del caso
     */
    protected function extractKeywords(LegalCase $case): array
    {
        $text = strtolower($case->title . ' ' . $case->description . ' ' . $case->facts);

        // Keywords legales comunes por tipo de caso
        $legalKeywords = [
            'civil' => ['responsabilidad', 'daños', 'negligencia', 'accidente', 'indemnización'],
            'penal' => ['delito', 'imputado', 'culpabilidad', 'condena', 'absolución'],
            'laboral' => ['despido', 'contrato', 'salario', 'indemnización', 'acoso'],
        ];

        $keywords = $legalKeywords[$case->case_type] ?? [];

        // Agregar palabras clave del texto
        $words = explode(' ', $text);
        $importantWords = array_filter($words, fn($w) => strlen($w) > 6);

        return array_unique(array_merge($keywords, array_slice($importantWords, 0, 5)));
    }

    /**
     * Query por defecto
     */
    protected function getDefaultQuery(LegalCase $case): string
    {
        return "{$case->case_type} {$case->title}";
    }

    /**
     * Calcular confianza del resultado
     */
    protected function calculateConfidence(array $precedents): float
    {
        if (empty($precedents)) {
            return 0.3;
        }

        $avgSimilarity = array_sum(array_column($precedents, 'similarity_score')) / count($precedents);
        $count = count($precedents);

        // Confianza basada en cantidad y calidad de precedentes
        $confidence = ($avgSimilarity * 0.7) + (min($count / 10, 1.0) * 0.3);

        return round($confidence, 2);
    }

    /**
     * Buscar jurisprudencia en internet usando MCP Client
     *
     * Esta función demuestra el uso de MCP Client-Server según requerimiento del hackathon:
     * "MCP Client: consumir algún MCP Server que exponga alguna funcionalidad básica"
     */
    protected function searchWebJurisprudence(string $query, LegalCase $case): array
    {
        // Si no hay MCP Web Search disponible, retornar vacío
        if (!$this->mcpWebSearch || !$this->mcpWebSearch->isAvailable()) {
            return [];
        }

        try {
            // Invocar MCP Server para buscar en internet
            $webResults = $this->mcpWebSearch->searchJurisprudence($query, 5);

            // Convertir resultados web a formato de precedentes
            $precedents = [];

            foreach ($webResults as $result) {
                $precedents[] = [
                    'jurisprudence' => (object)[
                        'uuid' => 'web-' . md5($result['url']),
                        'case_number' => 'WEB-SEARCH',
                        'case_title' => $result['title'],
                        'court' => 'Fuente Web',
                        'decision_date' => now(),
                        'summary' => $result['description'],
                        'ruling' => 'N/A',
                        'legal_reasoning' => $result['description'],
                        'keywords' => [],
                        'articles_cited' => [],
                        'relevance_level' => $result['relevance'] > 0.7 ? 'high' : 'medium',
                        'url' => $result['url'],
                        'source' => 'MCP Web Search',
                    ],
                    'similarity_score' => $result['relevance'],
                    'is_web_result' => true,
                ];
            }

            return $precedents;

        } catch (\Exception $e) {
            \Log::error("Error en búsqueda web MCP: " . $e->getMessage());
            return [];
        }
    }
}
