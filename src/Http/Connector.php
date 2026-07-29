<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
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
    ) {}

    /**
     * Envia uma requisição GET.
     *
     * @param  string  $domain    Domínio da API (self::DOMAIN_*)
     * @param  string  $path      Caminho do endpoint (ex.: /batch)
     * @param  array<string, mixed>  $query    Parâmetros de query string
     * @param  string|null  $accountId  ID da conta digital (Hub de Contas)
     */
    public function get(
        string $domain,
        string $path,
        array $query = [],
        ?string $accountId = null,
    ): array {
        $request = $this->buildRequest($domain, $accountId);

        $response = $request->get($this->url($domain, $path), $query);

        return $this->handleResponse($response);
    }

    /**
     * Envia uma requisição POST com payload JSON.
     *
     * @param  string  $domain    Domínio da API
     * @param  string  $path      Caminho do endpoint
     * @param  array<string, mixed>  $data      Payload da requisição
     * @param  string|null  $accountId  ID da conta digital
     */
    public function post(
        string $domain,
        string $path,
        array $data = [],
        ?string $accountId = null,
    ): array {
        $request = $this->buildRequest($domain, $accountId);

        $response = $request->post($this->url($domain, $path), $data);

        return $this->handleResponse($response);
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
        $request = $this->buildRequest($domain, $accountId);

        $response = $request->put($this->url($domain, $path), $data);

        return $this->handleResponse($response);
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
        $request = $this->buildRequest($domain, $accountId);

        $response = $request->patch($this->url($domain, $path), $data);

        return $this->handleResponse($response);
    }

    /**
     * Envia uma requisição DELETE.
     */
    public function delete(
        string $domain,
        string $path,
        ?string $accountId = null,
    ): array {
        $request = $this->buildRequest($domain, $accountId);

        $response = $request->delete($this->url($domain, $path));

        return $this->handleResponse($response);
    }

    /**
     * Constrói a URL completa para o domínio e caminho.
     */
    private function url(string $domain, string $path): string
    {
        $baseUrl = $this->baseUrls[$domain] ?? throw new TransfeeraException(
            message: "Domínio desconhecido: {$domain}",
        );

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Monta o PendingRequest com headers, auth, mTLS, timeout e retry.
     */
    private function buildRequest(string $domain, ?string $accountId): PendingRequest
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
            );

        // Aplica mTLS apenas nos domínios que exigem (payments e conta_certa)
        if (in_array($domain, [self::DOMAIN_PAYMENTS, self::DOMAIN_CONTA_CERTA], true)) {
            return $this->mtls->apply($request);
        }

        return $request;
    }

    /**
     * Processa a resposta, mapeando erros HTTP para exceptions tipadas.
     *
     * @return array<string, mixed>
     *
     * @throws TransfeeraException
     * @throws TransfeeraAuthenticationException
     * @throws TransfeeraValidationException
     * @throws TransfeeraRateLimitException
     */
    private function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?? [];
        }

        $payload = $response->json();
        $status = $response->status();
        $message = $payload['message'] ?? $payload['error'] ?? $response->body();

        return match (true) {
            $status === 401 => throw new TransfeeraAuthenticationException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
            $status === 422 => throw new TransfeeraValidationException(
                message: $message,
                statusCode: $status,
                errors: $payload['errors'] ?? $payload ?? [],
                payload: $payload,
            ),
            $status === 429 => throw new TransfeeraRateLimitException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
            default => throw new TransfeeraException(
                message: $message,
                statusCode: $status,
                payload: $payload,
            ),
        };
    }
}
