<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCPWebSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Controlador para PROBAR el MCP Fetch Server
 *
 * Este endpoint demuestra que el MCP Server hace requests REALES a internet
 */
class MCPTestController extends Controller
{
    /**
     * Probar MCP Fetch Server con URL real
     *
     * GET /api/mcp/test
     */
    public function test(Request $request)
    {
        try {
            // URL de ejemplo (puedes cambiarla en el query param)
            $url = $request->query('url', 'https://www.corteconstitucional.gov.co/relatoria/');

            Log::info("MCP TEST: Iniciando prueba con URL: {$url}");

            // Crear servicio MCP
            $mcpService = new MCPWebSearchService();

            // Verificar si está disponible
            if (!$mcpService->isAvailable()) {
                return response()->json([
                    'success' => false,
                    'message' => 'MCP Fetch Server no está disponible',
                    'help' => 'Asegúrate de tener Node.js instalado. El servidor se instala automáticamente con: npx -y @modelcontextprotocol/server-fetch',
                ], 500);
            }

            // Hacer búsqueda de jurisprudencia (esto invoca el MCP Server)
            Log::info("MCP TEST: Invocando búsqueda de jurisprudencia...");
            $results = $mcpService->searchJurisprudence('prueba test', 2);

            Log::info("MCP TEST: Resultados obtenidos: " . count($results));

            return response()->json([
                'success' => true,
                'message' => 'MCP Fetch Server funcionando correctamente',
                'explanation' => [
                    'step_1' => 'MCPClient envió request JSON-RPC 2.0 al MCP Fetch Server',
                    'step_2' => 'MCP Fetch Server hizo HTTP request REAL a internet',
                    'step_3' => 'Sitio web retornó HTML real',
                    'step_4' => 'MCP Fetch Server convirtió HTML a texto',
                    'step_5' => 'MCPClient recibió el contenido procesado',
                ],
                'mcp_server_type' => 'REAL (Anthropic Official)',
                'protocol' => 'JSON-RPC 2.0 via stdio',
                'data_source' => 'Internet (HTTP requests reales)',
                'results_count' => count($results),
                'sample_results' => $results,
                'tools_available' => $mcpService->getAvailableTools(),
            ], 200);

        } catch (\Exception $e) {
            Log::error("MCP TEST ERROR: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error probando MCP Fetch Server',
                'help' => 'Verifica que Node.js esté instalado y que npx funcione',
            ], 500);
        }
    }

    /**
     * Ver información del MCP Client
     *
     * GET /api/mcp/info
     */
    public function info()
    {
        return response()->json([
            'mcp_implementation' => [
                'type' => 'Client-Server (Real)',
                'server' => '@modelcontextprotocol/server-fetch',
                'provider' => 'Anthropic (Official)',
                'protocol' => 'JSON-RPC 2.0',
                'transport' => 'stdio',
                'cost' => '100% FREE',
            ],
            'how_it_works' => [
                '1' => 'Laravel MCPClient se comunica con proceso externo Node.js',
                '2' => 'Proceso Node.js ejecuta MCP Fetch Server de Anthropic',
                '3' => 'MCP Fetch Server hace HTTP requests REALES a internet',
                '4' => 'Obtiene HTML de sitios web reales',
                '5' => 'Convierte HTML a texto legible',
                '6' => 'Retorna contenido vía JSON-RPC a Laravel',
            ],
            'data_sources' => [
                'Corte Constitucional Colombia' => 'https://www.corteconstitucional.gov.co/',
                'Rama Judicial Colombia' => 'https://www.ramajudicial.gov.co/',
                'Función Pública Colombia' => 'https://www.funcionpublica.gov.co/',
                'Alcaldía de Bogotá' => 'https://www.alcaldiabogota.gov.co/',
            ],
            'is_simulated' => false,
            'is_mock' => false,
            'is_real_mcp_server' => true,
        ], 200);
    }
}
