<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Middleware;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de logging para requisições à API Transfeera.
 *
 * Laravel 13+ usa Guzzle HandlerStack diretamente no withMiddleware(),
 * então este middleware não é mais registrado como handler de pilha.
 * O Connector chama log() diretamente após cada requisição.
 */
class LoggingMiddleware
{
    /**
     * @param  bool   $enabled    Se o logging está ativo
     * @param  string $channel    Canal do Log (default: stack)
     * @param  string $level      Nível padrão: 'info'
     * @param  bool   $logHeaders Se deve incluir headers no contexto
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly string $channel = 'stack',
        public readonly string $level = 'info',
        public readonly bool $logHeaders = false,
    ) {
    }

    /**
     * Registra log de uma requisição e resposta da API Transfeera.
     *
     * @param  string   $method   Método HTTP (GET, POST, etc.)
     * @param  string   $url      URL completa da requisição
     * @param  array    $data     Payload enviado
     * @param  Response|null $response Resposta recebida (pode ser nula em caso de erro antes da requisição)
     * @param  float    $duration Duração em segundos
     */
    public function log(string $method, string $url, array $data, ?Response $response, float $duration): void
    {
        if (! $this->enabled) {
            return;
        }

        $level = $response instanceof Response && ($response->successful() || $response->status() < 500)
            ? $this->level
            : 'warning';

        $message = sprintf(
            'Transfeera API %s %s - %s (%.2fms)',
            strtoupper($method),
            $url,
            (string) ($response?->status() ?? 'N/A'),
            $duration * 1000,
        );

        $context = [
            'method' => $method,
            'url' => $url,
            'status' => $response?->status(),
            'duration_ms' => round($duration * 1000, 2),
        ];

        if ($this->logHeaders) {
            $context['request_data'] = $data;
        }

        Log::channel($this->channel)->log($level, $message, $context);
    }
}
