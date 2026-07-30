<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Middleware;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware para logging de requisições e respostas HTTP.
 */
class LoggingMiddleware
{
    public function __construct(
        public readonly bool $enabled = false,
        public readonly string $logLevel = 'info',
        public readonly bool $logHeaders = false,
        public readonly bool $logBody = false,
        public readonly int $maxBodyLength = 2000,
    ) {}

    public function handle(PendingRequest $request, callable $next): Response
    {
        if (! $this->enabled) {
            return $next($request);
        }

        $startTime = microtime(true);

        // Log da requisição
        $this->logRequest($request);

        $response = $next($request);

        // Log da resposta
        $this->logResponse($request, $response, microtime(true) - $startTime);

        return $response;
    }

    private function logRequest(PendingRequest $request): void
    {
        $options = $request->getOptions();
        $domain = $options['transfeera_domain'] ?? 'unknown';
        $method = $options['transfeera_method'] ?? 'GET';
        $url = $options['transfeera_url'] ?? 'unknown';

        $data = [
            'domain' => $domain,
            'method' => $method,
            'url' => $url,
        ];

        if ($this->logHeaders) {
            $data['headers'] = $this->sanitizeHeaders($request->headers() ?? []);
        }

        if ($this->logBody) {
            $data['body'] = $this->truncate((string) $request->getBody() ?? '');
        }

        Log::log($this->logLevel, 'Transfeera API Request', $data);
    }

    private function logResponse(PendingRequest $request, Response $response, float $duration): void
    {
        $options = $request->getOptions();
        $domain = $options['transfeera_domain'] ?? 'unknown';
        $method = $options['transfeera_method'] ?? 'GET';
        $url = $options['transfeera_url'] ?? 'unknown';

        $data = [
            'domain' => $domain,
            'method' => $method,
            'url' => $url,
            'status' => $response->status(),
            'duration_ms' => round($duration * 1000, 2),
            'successful' => $response->successful(),
        ];

        if ($this->logHeaders) {
            $data['response_headers'] = $this->sanitizeHeaders($response->headers());
        }

        if ($this->logBody) {
            $data['response_body'] = $this->truncate($response->body());
        }

        $level = $response->successful() ? $this->logLevel : 'warning';
        Log::log($level, 'Transfeera API Response', $data);
    }

    private function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'x-signature', 'cookie', 'set-cookie'];
        $sanitized = [];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower((string) $key), $sensitive, true)) {
                $sanitized[$key] = '[REDACTED]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    private function truncate(string $body): string
    {
        if (strlen($body) <= $this->maxBodyLength) {
            return $body;
        }

        return substr($body, 0, $this->maxBodyLength) . '... [truncated]';
    }
}