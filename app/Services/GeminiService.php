<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Exception;

class GeminiService
{
    protected Client $client;
    protected ?string $apiKey;
    protected string $model;
    protected string $embeddingModel;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://generativelanguage.googleapis.com/',
            'timeout' => 60,
        ]);

        $this->apiKey = config('services.gemini.api_key');
        $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        $this->embeddingModel = config('services.gemini.embedding_model', 'text-embedding-004');

        if (empty($this->apiKey)) {
            Log::warning("API key de Gemini no configurada. Usando modo fallback.");
        }
    }

    /**
     * Genera texto usando Gemini
     */
    public function generateText(string $prompt): string
    {
        if (empty($this->apiKey)) {
            return $this->fallbackResponse($prompt);
        }

        try {
            $response = $this->client->post("v1/models/{$this->model}:generateContent", [
                'query' => ['key' => $this->apiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 2048,
                    ]
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new Exception('Respuesta inesperada de Gemini: ' . json_encode($data));

        } catch (Exception $e) {
            Log::error("Error en Gemini generateText: " . $e->getMessage());
            return $this->fallbackResponse($prompt);
        }
    }

    /**
     * Genera respuesta estructurada en JSON
     */
    public function generateStructured(string $prompt, array $schema = []): array
    {
        if (empty($this->apiKey)) {
            return $this->fallbackStructuredResponse();
        }

        try {
            // Agregar instrucción para responder en JSON
            $jsonPrompt = $prompt . "\n\nResponde ÚNICAMENTE con un objeto JSON válido, sin texto adicional antes ni después.";

            $response = $this->generateText($jsonPrompt);

            // Limpiar la respuesta para extraer solo el JSON
            $response = trim($response);

            // Eliminar markdown code blocks si existen
            $response = preg_replace('/```json\s*/', '', $response);
            $response = preg_replace('/```\s*$/', '', $response);
            $response = trim($response);

            $decoded = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Respuesta no es JSON válido: ' . $response);
            }

            return $decoded;

        } catch (Exception $e) {
            Log::error("Error en Gemini generateStructured: " . $e->getMessage());
            return $this->fallbackStructuredResponse();
        }
    }

    /**
     * Analiza una imagen usando Gemini Vision
     */
    public function analyzeImage(string $imageUrl, string $prompt): string
    {
        if (empty($this->apiKey)) {
            return $this->fallbackImageAnalysis();
        }

        try {
            // Descargar la imagen y convertirla a base64
            $imageData = file_get_contents($imageUrl);
            if ($imageData === false) {
                throw new Exception("No se pudo descargar la imagen desde: {$imageUrl}");
            }

            $base64Image = base64_encode($imageData);
            $mimeType = $this->getMimeType($imageUrl);

            $response = $this->client->post("v1/models/gemini-1.5-flash:generateContent", [
                'query' => ['key' => $this->apiKey],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Image
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.4,
                        'maxOutputTokens' => 1024,
                    ]
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }

            throw new Exception('Respuesta inesperada de Gemini Vision');

        } catch (Exception $e) {
            Log::error("Error en Gemini analyzeImage: " . $e->getMessage());
            return $this->fallbackImageAnalysis();
        }
    }

    /**
     * Genera embeddings usando Gemini
     */
    public function generateEmbedding(string $text): array
    {
        if (empty($this->apiKey)) {
            return $this->fallbackEmbedding();
        }

        try {
            $response = $this->client->post("v1/models/{$this->embeddingModel}:embedContent", [
                'query' => ['key' => $this->apiKey],
                'json' => [
                    'content' => [
                        'parts' => [
                            ['text' => $text]
                        ]
                    ]
                ],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['embedding']['values'])) {
                return $data['embedding']['values'];
            }

            throw new Exception('Respuesta inesperada de Gemini Embeddings');

        } catch (Exception $e) {
            Log::error("Error en Gemini generateEmbedding: " . $e->getMessage());
            return $this->fallbackEmbedding();
        }
    }

    /**
     * Obtiene el MIME type de una URL de imagen
     */
    protected function getMimeType(string $url): string
    {
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));

        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        return $mimeTypes[$extension] ?? 'image/jpeg';
    }

    /**
     * Respuesta fallback para texto
     */
    protected function fallbackResponse(string $prompt): string
    {
        return "Respuesta simulada de Gemini. Configure GEMINI_API_KEY para usar IA real.";
    }

    /**
     * Respuesta fallback estructurada
     */
    protected function fallbackStructuredResponse(): array
    {
        return [
            'message' => 'Respuesta simulada. Configure GEMINI_API_KEY para usar IA real.',
            'status' => 'fallback'
        ];
    }

    /**
     * Respuesta fallback para análisis de imagen
     */
    protected function fallbackImageAnalysis(): string
    {
        return "Análisis visual simulado. Configure GEMINI_API_KEY para usar análisis real con Gemini Vision.";
    }

    /**
     * Embedding fallback (vector aleatorio de 768 dimensiones para Gemini)
     */
    protected function fallbackEmbedding(): array
    {
        // Gemini text-embedding-004 genera vectores de 768 dimensiones
        $embedding = [];
        for ($i = 0; $i < 768; $i++) {
            $embedding[] = (mt_rand(-100, 100) / 100);
        }
        return $embedding;
    }
}
