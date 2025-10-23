<?php

namespace App\Services;

/**
 * Mock MCP Server Local (para demo sin APIs externas)
 *
 * Este servidor simula un MCP Server localmente para demostrar
 * el protocolo MCP Client-Server sin necesidad de APIs externas.
 *
 * Para el hackathon: Demuestra comprensión del protocolo MCP
 * sin requerir tarjetas de crédito ni configuración externa.
 */
class MCPLocalMockServer
{
    /**
     * Simular búsqueda de jurisprudencia
     *
     * Retorna resultados pre-configurados que simulan búsquedas web reales
     */
    public static function searchJurisprudence(string $query, int $maxResults = 5): array
    {
        // Casos legales COLOMBIANOS simulados basados en el query
        $mockResults = [
            [
                'title' => 'Sentencia Corte Constitucional T-123/2024',
                'url' => 'https://www.corteconstitucional.gov.co/relatoria/2024/T-123-24.htm',
                'description' => 'Incumplimiento de contrato de servicios. La Corte Constitucional establece que existe responsabilidad contractual cuando una de las partes no cumple las obligaciones pactadas sin causa justificada conforme al Código Civil colombiano.',
                'source' => 'MCP Mock Server (Colombia Demo)',
                'relevance' => 0.92,
            ],
            [
                'title' => 'Sentencia Corte Suprema de Justicia - Radicado 11001-31-03-001-2023-00456-01',
                'url' => 'https://www.ramajudicial.gov.co/documents/sentencia-2023-00456.pdf',
                'description' => 'Precedente relevante sobre daños y perjuicios en contratos de construcción. Se establece el cálculo de indemnización por lucro cesante y daño emergente según el artículo 1613 del Código Civil.',
                'source' => 'MCP Mock Server (Colombia Demo)',
                'relevance' => 0.88,
            ],
            [
                'title' => 'Jurisprudencia - Responsabilidad Civil Extracontractual Colombia',
                'url' => 'https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php?i=39535',
                'description' => 'Análisis de la culpa como elemento esencial de la responsabilidad civil extracontractual. Aplicación del artículo 2341 del Código Civil Colombiano.',
                'source' => 'MCP Mock Server (Colombia Demo)',
                'relevance' => 0.85,
            ],
            [
                'title' => 'Tribunal Superior de Bogotá - Indemnización por Daño Moral',
                'url' => 'https://www.alcaldiabogota.gov.co/sisjur/normas/Norma1.jsp?i=4444',
                'description' => 'Criterios para la determinación del monto de indemnización por daño moral en casos de incumplimiento contractual según jurisprudencia colombiana reciente.',
                'source' => 'MCP Mock Server (Colombia Demo)',
                'relevance' => 0.81,
            ],
            [
                'title' => 'Sentencia Emblemática - Resolución de Contratos en Colombia',
                'url' => 'https://www.corteconstitucional.gov.co/relatoria/2023/C-456-23.htm',
                'description' => 'Aplicación de la condición resolutoria tácita del artículo 1546 del Código Civil colombiano. Requisitos para la resolución contractual y efectos jurídicos.',
                'source' => 'MCP Mock Server (Colombia Demo)',
                'relevance' => 0.79,
            ],
        ];

        // Filtrar y adaptar según el query
        $results = array_slice($mockResults, 0, $maxResults);

        // Personalizar título basado en el query
        foreach ($results as &$result) {
            if (stripos($query, 'penal') !== false) {
                $result['title'] = str_replace('Contrato', 'Delito', $result['title']);
            } elseif (stripos($query, 'laboral') !== false) {
                $result['title'] = str_replace('Contrato', 'Despido', $result['title']);
            }
        }

        return $results;
    }

    /**
     * Verificar disponibilidad del mock server
     */
    public static function isAvailable(): bool
    {
        return true; // Siempre disponible
    }

    /**
     * Obtener herramientas disponibles (simula MCP tools/list)
     */
    public static function getTools(): array
    {
        return [
            [
                'name' => 'search_jurisprudence',
                'description' => 'Busca precedentes legales y jurisprudencia (simulado localmente)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'max_results' => ['type' => 'integer']
                    ]
                ]
            ]
        ];
    }
}
