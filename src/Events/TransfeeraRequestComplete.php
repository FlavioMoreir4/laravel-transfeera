<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado após cada requisição à API Transfeera.
 *
 * Carrega metadados completos da requisição para que listeners
 * possam encaminhar para sistemas de observabilidade (Prometheus,
 * OpenTelemetry, StatsD, logging avançado, etc.).
 *
 * @example
 * ```php
 * // No AppServiceProvider::boot():
 * Event::listen(TransfeeraRequestComplete::class, function ($event) {
 *     // Enviar para OpenTelemetry
 *     $tracer->span('transfeera.api')
 *         ->setAttribute('http.method', $event->method)
 *         ->setAttribute('http.url', $event->url)
 *         ->setAttribute('http.status_code', $event->status)
 *         ->end();
 *
 *     // Ou prometheus
 *     prometheus_histogram('transfeera_request_duration_seconds')
 *         ->observe($event->duration);
 * });
 * ```
 */
class TransfeeraRequestComplete
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string  $domain  Domínio da API ('payments', 'receivables', etc.)
     * @param  string  $method  Método HTTP (GET, POST, PUT, PATCH, DELETE)
     * @param  string  $url  URL completa da requisição
     * @param  int  $status  Código HTTP de resposta
     * @param  float  $duration  Duração em segundos
     * @param  array<string, mixed>  $responseData  Payload de resposta (se houver)
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $method,
        public readonly string $url,
        public readonly int $status,
        public readonly float $duration,
        public readonly array $responseData = [],
    ) {}
}
