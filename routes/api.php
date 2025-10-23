<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CaseController;
use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\JurisprudenceController;
use App\Http\Controllers\Api\EvidenceController;
use App\Http\Controllers\Api\MCPTestController;
use App\Http\Controllers\Api\AuthController;

// ============================================
// AUTENTICACIÓN (Authentication)
// ============================================

// Rutas públicas de autenticación
Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas de autenticación
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
});

// Ruta de autenticación legacy (compatibilidad)
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Ruta de health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'LEGAL-IA API',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Ruta de test simple
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working! 🚀',
        'status' => 'success',
        'environment' => app()->environment(),
        'php_version' => phpversion(),
        'laravel_version' => app()->version(),
    ]);
});

// CASOS LEGALES (Legal Cases)

// Estadísticas de casos (antes de resource para evitar conflicto)
Route::get('/cases/stats', [CaseController::class, 'stats']);

// CRUD de casos
Route::apiResource('cases', CaseController::class)->parameters([
    'cases' => 'uuid' // Usar UUID en lugar de ID
]);


// ANÁLISIS (Analysis)

// Iniciar análisis de un caso
Route::post('/cases/{uuid}/analyze', [AnalysisController::class, 'analyze']);

// Re-analizar un caso (crear nueva versión)
Route::post('/cases/{uuid}/re-analyze', [AnalysisController::class, 'reanalyze']);

// Obtener análisis de un caso
Route::get('/cases/{uuid}/analysis', [AnalysisController::class, 'getAnalysis']);

// Obtener último análisis completado de un caso
Route::get('/cases/{uuid}/analysis/latest', [AnalysisController::class, 'getLatestAnalysis']);

// Ver análisis específico por UUID
Route::get('/analysis/{uuid}', [AnalysisController::class, 'show']);

// Cancelar análisis en proceso
Route::post('/analysis/{uuid}/cancel', [AnalysisController::class, 'cancel']);

// Estadísticas de análisis
Route::get('/analysis/stats', [AnalysisController::class, 'stats']);

// JURISPRUDENCIA (Jurisprudence)

// Búsquedas especiales (antes de resource)
Route::post('/jurisprudence/search', [JurisprudenceController::class, 'search']);
Route::post('/jurisprudence/semantic-search', [JurisprudenceController::class, 'semanticSearch']);
Route::post('/jurisprudence/find-similar', [JurisprudenceController::class, 'findSimilar']);

// Estadísticas de jurisprudencia
Route::get('/jurisprudence/stats', [JurisprudenceController::class, 'stats']);

// CRUD de jurisprudencia
Route::apiResource('jurisprudence', JurisprudenceController::class)->parameters([
    'jurisprudence' => 'uuid'
]);

// EVIDENCIA (Evidence)

// Evidencia de un caso específico
Route::get('/cases/{uuid}/evidence', [EvidenceController::class, 'index']);
Route::post('/cases/{uuid}/evidence', [EvidenceController::class, 'store']);
Route::post('/cases/{uuid}/evidence/bulk', [EvidenceController::class, 'bulkUpload']);

// CRUD de evidencia individual
Route::get('/evidence/{uuid}', [EvidenceController::class, 'show']);
Route::put('/evidence/{uuid}', [EvidenceController::class, 'update']);
Route::patch('/evidence/{uuid}', [EvidenceController::class, 'update']);
Route::delete('/evidence/{uuid}', [EvidenceController::class, 'destroy']);

// Análisis de evidencia visual
Route::post('/evidence/{uuid}/analyze', [EvidenceController::class, 'analyzeVisual']);

// MCP TEST (Para demostrar que MCP es REAL, no simulado)
Route::get('/mcp/test', [MCPTestController::class, 'test']);
Route::get('/mcp/info', [MCPTestController::class, 'info']);
Route::get('/evidence/{uuid}/analysis', [EvidenceController::class, 'getAnalysis']);

// ============================================
// RUTAS AGRUPADAS CON MIDDLEWARE (Opcional)
// ============================================

// Si quieres agregar autenticación más adelante, puedes usar:
/*
Route::middleware('auth:sanctum')->group(function () {
    // Todas las rutas protegidas aquí
});
*/

// Si quieres agregar rate limiting:
/*
Route::middleware('throttle:60,1')->group(function () {
    // Rutas con límite de 60 requests por minuto
});
*/
