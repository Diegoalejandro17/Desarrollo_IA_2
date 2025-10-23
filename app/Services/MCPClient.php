<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Exception;

/**
 * MCP Client (Model Context Protocol)
 *
 * Cliente para conectarse a servidores MCP externos siguiendo el estándar
 * definido por Anthropic: https://spec.modelcontextprotocol.io/
 *
 * Este cliente implementa:
 * - Conexión stdio con servidores MCP
 * - Protocol de mensajes JSON-RPC 2.0
 * - Invocación de herramientas (tools) expuestas por el servidor
 */
class MCPClient
{
    protected ?array $serverProcess = null;
    protected $pipes = [];
    protected array $tools = [];
    protected string $serverName;

    /**
     * Inicializar cliente MCP con un servidor específico
     *
     * @param string $serverCommand Comando para iniciar el servidor MCP (ej: "npx -y @modelcontextprotocol/server-brave-search")
     * @param array $env Variables de entorno para el servidor
     */
    public function __construct(string $serverCommand, array $env = [])
    {
        $this->serverName = $serverCommand;

        try {
            // Inicializar conexión con el servidor MCP
            $this->connect($serverCommand, $env);

            // Obtener lista de herramientas disponibles
            $this->discoverTools();

        } catch (Exception $e) {
            Log::warning("No se pudo conectar al servidor MCP: " . $e->getMessage());
            // No lanzar excepción, permitir que la app funcione sin MCP
        }
    }

    /**
     * Conectar con el servidor MCP via stdio
     */
    protected function connect(string $command, array $env): void
    {
        $descriptorspec = [
            0 => ["pipe", "r"],  // stdin
            1 => ["pipe", "w"],  // stdout
            2 => ["pipe", "w"]   // stderr
        ];

        $process = proc_open(
            $command,
            $descriptorspec,
            $pipes,
            null,
            $env
        );

        if (!is_resource($process)) {
            throw new Exception("No se pudo iniciar el servidor MCP");
        }

        $this->serverProcess = ['process' => $process];
        $this->pipes = $pipes;

        // Configurar pipes como no bloqueantes
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        // Enviar mensaje de inicialización (handshake MCP)
        $this->sendMessage([
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2024-11-05',
                'capabilities' => [
                    'tools' => []
                ],
                'clientInfo' => [
                    'name' => 'LEGAL-IA',
                    'version' => '1.0.0'
                ]
            ]
        ]);

        // Esperar respuesta de inicialización
        $response = $this->readMessage();

        if (!isset($response['result'])) {
            throw new Exception("Fallo en handshake MCP");
        }

        Log::info("Conexión MCP establecida con: " . $this->serverName);
    }

    /**
     * Descubrir herramientas disponibles en el servidor
     */
    protected function discoverTools(): void
    {
        $this->sendMessage([
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/list'
        ]);

        $response = $this->readMessage();

        if (isset($response['result']['tools'])) {
            $this->tools = $response['result']['tools'];
            Log::info("MCP Tools descubiertas: " . count($this->tools));
        }
    }

    /**
     * Invocar una herramienta del servidor MCP
     *
     * @param string $toolName Nombre de la herramienta
     * @param array $arguments Argumentos para la herramienta
     * @return mixed Resultado de la herramienta
     */
    public function callTool(string $toolName, array $arguments = [])
    {
        if (empty($this->serverProcess)) {
            Log::warning("MCP Client no conectado, retornando null");
            return null;
        }

        try {
            $this->sendMessage([
                'jsonrpc' => '2.0',
                'id' => rand(100, 9999),
                'method' => 'tools/call',
                'params' => [
                    'name' => $toolName,
                    'arguments' => $arguments
                ]
            ]);

            $response = $this->readMessage();

            if (isset($response['error'])) {
                throw new Exception("Error MCP: " . json_encode($response['error']));
            }

            return $response['result'] ?? null;

        } catch (Exception $e) {
            Log::error("Error invocando tool MCP '{$toolName}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener lista de herramientas disponibles
     */
    public function getAvailableTools(): array
    {
        return $this->tools;
    }

    /**
     * Enviar mensaje al servidor MCP
     */
    protected function sendMessage(array $message): void
    {
        $json = json_encode($message) . "\n";
        fwrite($this->pipes[0], $json);
        fflush($this->pipes[0]);
    }

    /**
     * Leer mensaje del servidor MCP
     */
    protected function readMessage(int $timeoutMs = 5000): ?array
    {
        $startTime = microtime(true);
        $buffer = '';

        while ((microtime(true) - $startTime) < ($timeoutMs / 1000)) {
            $line = fgets($this->pipes[1]);

            if ($line === false) {
                usleep(10000); // 10ms
                continue;
            }

            $buffer .= $line;

            // Si tenemos un mensaje completo (termina en \n)
            if (substr($buffer, -1) === "\n") {
                $decoded = json_decode(trim($buffer), true);
                if ($decoded !== null) {
                    return $decoded;
                }
                $buffer = '';
            }
        }

        return null;
    }

    /**
     * Cerrar conexión con el servidor
     */
    public function disconnect(): void
    {
        if (!empty($this->pipes)) {
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
        }

        if (!empty($this->serverProcess)) {
            proc_terminate($this->serverProcess['process']);
            proc_close($this->serverProcess['process']);
        }

        Log::info("Conexión MCP cerrada");
    }

    /**
     * Destructor para asegurar que se cierre la conexión
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
