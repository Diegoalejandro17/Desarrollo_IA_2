<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LLMService
{
    protected string $provider;
    protected ?string $apiKey;
    protected string $model;

    /**
     * Constructor
     *
     * @param string $provider 'gemini', 'openai' o 'anthropic'
     */
    public function __construct(string $provider = 'gemini')
    {
        $this->provider = $provider;

        if ($provider === 'gemini') {
            $this->apiKey = config('services.gemini.api_key');
            $this->model = config('services.gemini.model', 'gemini-1.5-flash');
        } elseif ($provider === 'openai') {
            $this->apiKey = config('services.openai.api_key');
            $this->model = config('services.openai.model', 'gpt-4o');
        } else {
            $this->apiKey = config('services.anthropic.api_key');
            $this->model = config('services.anthropic.model', 'claude-3-5-sonnet-20241022');
        }

        // No lanzar excepción si no hay API key, permitir modo fallback
        if (empty($this->apiKey)) {
            Log::warning("API key no configurada para {$provider}. Usando modo fallback.");
        }
    }

    /**
     * Generar embedding de un texto (Gemini u OpenAI)
     *
     * @param string $text
     * @return array Vector de embeddings
     */
    public function generateEmbedding(string $text): array
    {
        if ($this->provider === 'gemini') {
            $gemini = new GeminiService();
            return $gemini->generateEmbedding($text);
        }

        if ($this->provider !== 'openai') {
            throw new Exception("Embeddings solo disponibles con Gemini u OpenAI");
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/embeddings', [
                'model' => 'text-embedding-3-small',
                'input' => $text,
            ]);

            if ($response->failed()) {
                throw new Exception("Error generando embedding: " . $response->body());
            }

            $data = $response->json();
            return $data['data'][0]['embedding'];

        } catch (Exception $e) {
            Log::error("Error en generateEmbedding: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Analizar texto con el LLM
     *
     * @param string $prompt
     * @param array $options Opciones adicionales (temperature, max_tokens, etc.)
     * @return string Respuesta del LLM
     */
    public function analyzeText(string $prompt, array $options = []): string
    {
        if ($this->provider === 'gemini') {
            $gemini = new GeminiService();
            return $gemini->generateText($prompt);
        } elseif ($this->provider === 'openai') {
            return $this->analyzeWithOpenAI($prompt, $options);
        } else {
            return $this->analyzeWithAnthropic($prompt, $options);
        }
    }

    /**
     * Analizar imagen con Gemini Vision o GPT-4 Vision
     *
     * @param string $imageUrl URL de la imagen
     * @param string $prompt Pregunta sobre la imagen
     * @return string Análisis de la imagen
     */
    public function analyzeImage(string $imageUrl, string $prompt): string
    {
        if ($this->provider === 'gemini') {
            $gemini = new GeminiService();
            return $gemini->analyzeImage($imageUrl, $prompt);
        }

        if ($this->provider !== 'openai') {
            throw new Exception("Análisis de imagen solo disponible con Gemini o OpenAI GPT-4 Vision");
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'text',
                                'text' => $prompt,
                            ],
                            [
                                'type' => 'image_url',
                                'image_url' => [
                                    'url' => $imageUrl,
                                ],
                            ],
                        ],
                    ],
                ],
                'max_tokens' => 1000,
            ]);

            if ($response->failed()) {
                throw new Exception("Error analizando imagen: " . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'];

        } catch (Exception $e) {
            Log::error("Error en analyzeImage: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Chat con el LLM (conversación con contexto)
     *
     * @param array $messages Array de mensajes [['role' => 'user', 'content' => '...']]
     * @param array $options
     * @return string
     */
    public function chat(array $messages, array $options = []): string
    {
        if ($this->provider === 'openai') {
            return $this->chatWithOpenAI($messages, $options);
        } else {
            return $this->chatWithAnthropic($messages, $options);
        }
    }

    /**
     * Análisis estructurado con JSON
     *
     * @param string $prompt
     * @param array $schema Schema JSON esperado
     * @return array
     */
    public function analyzeStructured(string $prompt, array $schema = []): array
    {
        if ($this->provider === 'gemini') {
            $gemini = new GeminiService();
            return $gemini->generateStructured($prompt, $schema);
        }

        $systemPrompt = "Eres un asistente legal experto. Responde SOLO con JSON válido sin markdown ni explicaciones adicionales.";

        $fullPrompt = $systemPrompt . "\n\n" . $prompt;

        if (!empty($schema)) {
            $fullPrompt .= "\n\nFormato JSON esperado:\n" . json_encode($schema, JSON_PRETTY_PRINT);
        }

        $response = $this->analyzeText($fullPrompt, [
            'temperature' => 0.3, // Más determinístico para JSON
        ]);

        // Limpiar la respuesta de markdown si viene con ```json
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        $response = trim($response);

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            Log::error("Error decodificando JSON: " . $e->getMessage() . "\nRespuesta: " . $response);
            throw new Exception("La respuesta del LLM no es JSON válido");
        }
    }

    // ========================================
    // Métodos privados para cada provider
    // ========================================

    private function analyzeWithOpenAI(string $prompt, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            if ($response->failed()) {
                throw new Exception("Error con OpenAI: " . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'];

        } catch (Exception $e) {
            Log::error("Error en analyzeWithOpenAI: " . $e->getMessage());
            throw $e;
        }
    }

    private function analyzeWithAnthropic(string $prompt, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            if ($response->failed()) {
                throw new Exception("Error con Anthropic: " . $response->body());
            }

            $data = $response->json();
            return $data['content'][0]['text'];

        } catch (Exception $e) {
            Log::error("Error en analyzeWithAnthropic: " . $e->getMessage());
            throw $e;
        }
    }

    private function chatWithOpenAI(array $messages, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'messages' => $messages,
                'temperature' => $options['temperature'] ?? 0.7,
                'max_tokens' => $options['max_tokens'] ?? 2000,
            ]);

            if ($response->failed()) {
                throw new Exception("Error con OpenAI: " . $response->body());
            }

            $data = $response->json();
            return $data['choices'][0]['message']['content'];

        } catch (Exception $e) {
            Log::error("Error en chatWithOpenAI: " . $e->getMessage());
            throw $e;
        }
    }

    private function chatWithAnthropic(array $messages, array $options = []): string
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $options['max_tokens'] ?? 2000,
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

            if ($response->failed()) {
                throw new Exception("Error con Anthropic: " . $response->body());
            }

            $data = $response->json();
            return $data['content'][0]['text'];

        } catch (Exception $e) {
            Log::error("Error en chatWithAnthropic: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Verificar si el servicio está disponible
     */
    public function healthCheck(): bool
    {
        try {
            $this->analyzeText("Responde solo 'OK'", ['max_tokens' => 10]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
