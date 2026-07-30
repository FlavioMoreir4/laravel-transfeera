<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\AccountException;
use FlavioMoreir4\Transfeera\Exceptions\ContaCertaException;
use FlavioMoreir4\Transfeera\Exceptions\InfractionException;
use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Exceptions\PixAutomaticoException;
use FlavioMoreir4\Transfeera\Exceptions\ReceivableException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
use FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware;
use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Wrapper sobre o HTTP Client do Laravel que gerencia:
 *
 * - Seleção automática da base URL por sub-API e ambiente
 * - Injeção do token de acesso (Bearer) nas requisições
 * - Aplicação condicional de mTLS (produção)
 * - Retry, timeout e User-Agent
 * - Middlewares de logging e métricas
 * - Mapeamento de erros HTTP para exceptions tipadas
 */
class Connector
{
    /** API de Autenticação */
    public const DOMAIN_AUTH = 'auth';

    /** API de Pagamentos/Recebimentos */
    public const DOMAIN_PAYMENTS = 'payments';

    /** API Conta Certa */
    public const DOMAIN_CONTA_CERTA = 'conta_certa';

    public function __construct(
        private readonly TokenManager $tokenManager,
        private readonly MtlsConfigurator $mtls,
        private readonly array $config,
        private readonly array $baseUrls,
        private readonly ?LoggingMiddleware $loggingMiddleware = null,
        private readonly ?MetricsMiddleware $metricsMiddleware = null,
    ) {}

    /**
     * Envia uma requisição GET.
     *
     * @param  string  $domain  Domínio da API (self::DOMAIN_*)
     * @param  string  $path  Caminho do endpoint (ex.: /batch)
     * @param  array<string, mixed>  $query  Parâmetros de query string
     * @param  string|null  $accountId  ID da conta digital (Hub de Contas)
     */
    public function get(
        string $domain,
        string $path,
        array $query = [],
        ?string $accountId = null,
    ): array {
        return $this->execute('GET', $domain, $path, $query, $accountId);
    }

    /**
     * Envia uma requisição POST com payload JSON.
     *
     * @param  string  $domain  Domínio da API
     * @param  string  $path  Caminho do endpoint
     * @param  array<string, mixed>  $data  Payload da requisição
     * @param  string|null  $accountId  ID da conta digital
     */
    public function post(
        string $domain,
        string $path,
        array $data = [],
        ?string $accountId = null,
    ): array {
        return $this->execute('POST', $domain, $path, $data, $accountId);
    }

    /**
     * Envia uma requisição PUT.
     */
    public function put(
        string $domain,
        string $path,
        array $data = [],
        ?string $accountId = null,
    ): array {
        return $this->execute('PUT', $domain, $path, $data, $accountId);
    }

    /**
     * Envia uma requisição PATCH.
     */
    public function patch(
        string $domain,
        string $path,
        array $data = [],
        ?string $accountId = null,
    ): array {
        return $this->execute('PATCH', $domain, $path, $data, $accountId);
    }

    /**
     * Envia uma requisição DELETE.
     */
    public function delete(
        string $domain,
        string $path,
        ?string $accountId = null,
    ): array {
        return $this->execute('DELETE', $domain, $path, [], $accountId);
    }

    /**
     * Executa uma requisição HTTP genérica.
     *
     * @param  string  $method  Método HTTP (GET, POST, PUT, PATCH, DELETE)
     * @param  string  $domain  Domínio da API
     * @param  string  $path  Caminho do endpoint
     * @param  array<string, mixed>  $data  Payload ou query params
     * @param  string|null  $accountId  ID da conta digital
     */
    private function execute(
        string $method,
        string $domain,
        string $path,
        array $data,
        ?string $accountId,
    ): array {
        $request = $this->buildRequest($domain, $accountId, $method, $path);
        $url = $this->url($domain, $path);

        $startTime = microtime(true);
        $response = null;

        try {
            $response = match ($method) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'PATCH' => $request->patch($url, $data),
                'DELETE' => $request->delete($url),
                default => throw new TransfeeraException(
                    message: "Método HTTP não suportado: {$method}",
                ),
            };

            return $this->handleResponse($response, $domain);
        } finally {
            $duration = microtime(true) - $startTime;

            if ($this->loggingMiddleware instanceof LoggingMiddleware && $this->loggingMiddleware->enabled) {
                $this->loggingMiddleware->log($method, $url, $data, $response ?? null, $duration);
            }

            if ($this->metricsMiddleware instanceof MetricsMiddleware && $this->metricsMiddleware->enabled) {
                $this->metricsMiddleware->recordMetric(
                    domain: $domain,
                    method: $method,
                    status: $response?->status() ?? 0,
                    duration: $duration,
                );
            }
        }
    }

    /**
     * Constrói a URL completa para o domínio e caminho.
     */
    private function url(string $domain, string $path): string
    {
        $baseUrl = $this->baseUrls[$domain] ?? throw new TransfeeraException(
            message: "Domínio desconhecido: {$domain}",
        );

        return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
    }

    /**
     * Monta o PendingRequest com headers, auth, mTLS, timeout, retry e middlewares.
     */
    private function buildRequest(string $domain, ?string $accountId, string $method, string $path): PendingRequest
    {
        $token = $this->tokenManager->getToken($accountId);

        $request = Http::withToken($token->token())
            ->withUserAgent($this->config['user_agent'] ?? 'Laravel Transfeera SDK')
            ->acceptJson()
            ->contentType('application/json')
            ->timeout($this->config['timeout'] ?? 30)
            ->retry(
                times: $this->config['retry']['max_attempts'] ?? 3,
                sleepMilliseconds: $this->config['retry']['delay_ms'] ?? 100,
                throw: false,
            );

        // Aplica middlewares se configurados
        // (obsoleto: middlewares são chamados diretamente no execute())

        // Armazena metadados para middlewares
        $request = $request->withOptions([
            'transfeera_domain' => $domain,
            'transfeera_method' => $method,
            'transfeera_url' => $this->url($domain, $path),
        ]);

        // Aplica mTLS apenas nos domínios que exigem (payments e conta_certa)
        if (in_array($domain, [self::DOMAIN_PAYMENTS, self::DOMAIN_CONTA_CERTA], true)) {
            return $this->mtls->apply($request);
        }

        return $request;
    }

    /**
     * Processa a resposta, mapeando erros HTTP para exceptions tipadas por domínio.
     *
     * @return array<string, mixed>
     *
     * @throws TransfeeraException
     * @throws TransfeeraAuthenticationException
     * @throws TransfeeraValidationException
     * @throws PaymentException
     * @throws ReceivableException
     * @throws PixAutomaticoException
     * @throws ContaCertaException
     * @throws AccountException
     * @throws InfractionException
     */
    private function handleResponse(Response $response, string $domain): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $payload = $response->json();
        $status = $response->status();
        $message = $payload['message'] ?? $payload['error'] ?? $response->body();

        // Mapeia códigos HTTP para exceptions base
        $baseException = match (true) {
            $status === 401 => new TransfeeraAuthenticationException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
            $status === 422 => new TransfeeraValidationException(
                message: $message,
                statusCode: $status,
                errors: $payload['errors'] ?? $payload ?? [],
                payload: $payload,
            ),
            $status === 429 => new TransfeeraRateLimitException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
            default => new TransfeeraException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
        };

        // Para erros de autenticação/validação/rate-limit, lança a exception base
        // (não são específicos de domínio)
        if (in_array($status, [401, 422, 429], true)) {
            throw $baseException;
        }

        // Lança exception específica do domínio para outros erros
        return match ($domain) {
            self::DOMAIN_PAYMENTS => throw new PaymentException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            'receivables' => throw new ReceivableException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            'pix_automatico' => throw new PixAutomaticoException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            self::DOMAIN_CONTA_CERTA => throw new ContaCertaException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            'accounts' => throw new AccountException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            'infractions' => throw new InfractionException(
                message: $baseException->getMessage(),
                statusCode: $baseException->getCode(),
                payload: $payload,
                previous: $baseException,
            ),
            default => throw $baseException,
        };
    }
}
