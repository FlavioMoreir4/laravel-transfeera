<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Middleware;

/**
 * Middleware de métricas para requisições à API Transfeera.
 *
 * O Connector chama recordMetric() diretamente após cada requisição.
 * A implementação real de envio de métricas (Prometheus, StatsD, etc.)
 * deve substituir este placeholder ou estendê-lo.
 */
class MetricsMiddleware
{
    /**
     * @param  bool   $enabled Se a coleta de métricas está ativa
     * @param  string $prefix  Prefixo para nomes das métricas
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly string $prefix = 'transfeera',
    ) {
    }

    /**
     * Registra uma métrica de requisição.
     *
     * Implementação placeholder — substitua pelo seu sistema de métricas.
     *
     * @param  string $domain   Domínio da API (payments, receivables, etc.)
     * @param  string $method   Método HTTP
     * @param  int    $status   Status HTTP da resposta
     * @param  float  $duration Duração em segundos
     */
    public function recordMetric(string $domain, string $method, int $status, float $duration): void
    {
        if (! $this->enabled) {
            return;
        }

        // Placeholder: registro interno para testes.
        // Em produção, envie para Prometheus/StatsD/OpenTelemetry:
        //
        //     $histogram->observe($duration, [
        //         'domain' => $domain,
        //         'method' => $method,
        //         'status' => (string) $status,
        //     ]);
    }
}
