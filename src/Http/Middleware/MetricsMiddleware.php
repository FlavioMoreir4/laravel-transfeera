<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Middleware;

use Throwable;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * Middleware para coleta de métricas de requisições HTTP.
 */
class MetricsMiddleware
{
    public function __construct(
        public readonly bool $enabled = false,
        public readonly string $prefix = 'transfeera',
    ) {}

    public function handle(PendingRequest $request, callable $next): Response
    {
        if (! $this->enabled) {
            return $next($request);
        }

        $startTime = microtime(true);

        try {
            $response = $next($request);

            $duration = microtime(true) - $startTime;
            $options = $request->getOptions();
            $domain = $options['transfeera_domain'] ?? 'unknown';
            $method = $options['transfeera_method'] ?? 'GET';
            $status = $response->status();

            $this->recordMetric();

            $this->recordMetric();

            if ($status >= 400) {
                $this->recordMetric();
            }

            return $response;
        } catch (Throwable $e) {
            $duration = microtime(true) - $startTime;
            $options = $request->getOptions();
            $domain = $options['transfeera_domain'] ?? 'unknown';
            $method = $options['transfeera_method'] ?? 'GET';

            $this->recordMetric();

            throw $e;
        }
    }

    private function recordMetric(): void
    {
        // Placeholder para integração com sistemas de métricas
        // Exemplo: app('prometheus')->increment($name, $tags);
        // Exemplo: app('statsd')->histogram($name, $value, $tags);
    }
}