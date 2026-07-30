<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Middleware;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;

/**
 * Middleware de logging para requisições à API Transfeera.
 *
 * Oferece sanitização de dados sensíveis, truncamento de payloads grandes,
 * níveis configuráveis por domínio/status, e suporte a log de response body.
 *
 * O Connector chama log() diretamente após cada requisição.
 */
class LoggingMiddleware
{
    /**
     * Campos considerados sensíveis, sanitizados com '***'.
     */
    private const array SENSITIVE_FIELDS = [
        'client_secret',
        'client_id',
        'token',
        'access_token',
        'password',
        'secret',
        'authorization',
    ];

    /**
     * Campos bancários/Pix que devem ser mascarados parcialmente.
     */
    private const array BANK_FIELDS = [
        'document',
        'cpf',
        'cnpj',
        'account',
        'agency',
        'phone',
        'email',
    ];

    /**
     * @param  bool  $enabled  Se o logging está ativo
     * @param  string  $channel  Canal do Log (default: stack)
     * @param  string  $level  Nível padrão: 'info'
     * @param  bool  $logHeaders  Se deve incluir payload no log
     * @param  bool  $logResponseBody  Se deve incluir corpo da resposta no log
     * @param  bool  $sanitize  Se deve sanitizar dados sensíveis
     * @param  positive-int  $maxBodyLength  Tamanho máximo do payload em caracteres
     * @param  array<string, string>  $levelByDomain  Mapa de domínio => nível
     * @param  array<int, string>  $levelByStatus  Mapa de status HTTP => nível
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly string $channel = 'stack',
        public readonly string $level = 'info',
        public readonly bool $logHeaders = false,
        public readonly bool $logResponseBody = false,
        public readonly bool $sanitize = true,
        public readonly int $maxBodyLength = 4096,
        public readonly array $levelByDomain = [],
        public readonly array $levelByStatus = [],
    ) {}

    /**
     * Registra log de uma requisição e resposta da API Transfeera.
     *
     * @param  string  $method  Método HTTP (GET, POST, etc.)
     * @param  string  $url  URL completa da requisição
     * @param  array  $data  Payload enviado
     * @param  Response|null  $response  Resposta recebida (pode ser nula em caso de erro antes da requisição)
     * @param  float  $duration  Duração em segundos
     */
    public function log(string $method, string $url, array $data, ?Response $response, float $duration): void
    {
        if (! $this->enabled) {
            return;
        }

        $status = $response?->status() ?? 0;
        $domain = $this->extractDomain($url);

        $level = $this->resolveLevel($domain, $status);

        if ($level === 'none') {
            return;
        }

        $message = sprintf(
            'Transfeera API %s %s - %s (%.2fms)',
            strtoupper($method),
            $url,
            (string) $status,
            $duration * 1000,
        );

        $context = [
            'method' => $method,
            'url' => $url,
            'domain' => $domain,
            'status' => $status,
            'duration_ms' => round($duration * 1000, 2),
        ];

        if ($this->logHeaders && $data !== []) {
            $requestData = $this->sanitize ? $this->sanitizeData($data) : $data;
            $context['request_data'] = $this->truncatePayload($requestData);
        }

        if ($this->logResponseBody && $response instanceof Response) {
            $responseData = $response->json() ?? [];
            $context['response_data'] = $this->sanitize
                ? $this->sanitizeData($responseData)
                : $responseData;
        }

        Log::channel($this->channel)->log($level, $message, $context);
    }

    /**
     * Extrai o domínio da URL.
     */
    private function extractDomain(string $url): string
    {
        if (str_contains($url, '/conta-certa/')) {
            return 'conta_certa';
        }

        if (str_contains($url, '/med/')) {
            return 'infractions';
        }

        if (str_contains($url, '/pix/automatico/') || str_contains($url, '/pix-automatico/')) {
            return 'pix_automatico';
        }

        if (str_contains($url, '/accounts/')) {
            return 'accounts';
        }

        return 'payments';
    }

    /**
     * Resolve o nível de log com base no domínio e status HTTP.
     *
     * 1. Se o status tem um nível específico (ex.: 5xx => error), usa esse.
     * 2. Se o domínio tem um nível específico (ex.: payments => debug), usa esse.
     * 3. Caso contrário, usa o nível padrão.
     */
    private function resolveLevel(string $domain, int $status): string
    {
        if (isset($this->levelByStatus[$status])) {
            return $this->levelByStatus[$status];
        }

        if ($status >= 500) {
            return 'error';
        }

        if ($status >= 400) {
            return 'warning';
        }

        return $this->levelByDomain[$domain] ?? $this->level;
    }

    /**
     * Sanitiza dados sensíveis no payload.
     *
     * @param  array  $data  Dados a sanitizar
     * @return array Dados com valores sensíveis mascarados
     */
    private function sanitizeData(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $lowerKey = strtolower((string) $key);

            if (in_array($lowerKey, self::SENSITIVE_FIELDS, true)) {
                $result[$key] = '***';
            } elseif (in_array($lowerKey, self::BANK_FIELDS, true) && is_string($value)) {
                $result[$key] = $this->maskValue($value);
            } elseif (is_array($value)) {
                $result[$key] = $this->sanitizeData($value);
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Mascara parcialmente um valor sensível, mantendo visíveis:
     * - Primeiros 2 caracteres
     * - Últimos 2 caracteres
     *
     * Para strings curtas (<= 4 chars), retorna '****'.
     */
    private function maskValue(string $value): string
    {
        $len = strlen($value);

        if ($len <= 4) {
            return '****';
        }

        $visible = 2;
        $maskedLen = $len - ($visible * 2);

        return substr($value, 0, $visible)
            .str_repeat('*', $maskedLen)
            .substr($value, -$visible);
    }

    /**
     * Trunca payloads grandes para evitar logs excessivos.
     *
     * @param  array  $data  Dados a truncar
     * @return array Dados truncados
     */
    private function truncatePayload(array $data): array
    {
        $json = json_encode($data);

        if ($json === false || strlen($json) <= $this->maxBodyLength) {
            return $data;
        }

        return [
            '_truncated' => true,
            '_original_size' => strlen($json),
            '_preview' => substr($json, 0, $this->maxBodyLength).'...',
        ];
    }
}
