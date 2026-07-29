<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Auth;

use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Gerencia o ciclo de vida do token de acesso OAuth 2.0 (client_credentials).
 *
 * Utiliza o cache do Laravel para armazenar o token e renova
 * automaticamente quando expirado, com lock para evitar requisições
 * concorrentes duplicadas.
 */
class TokenManager
{
    private const CACHE_KEY = 'transfeera_access_token';
    private const CACHE_LOCK_KEY = 'transfeera_token_lock';

    /**
     * @param  array<string, string>  $config  Configurações de autenticação
     */
    public function __construct(
        private readonly array $config,
        private readonly string $authBaseUrl,
    ) {}

    /**
     * Obtém um token de acesso válido.
     *
     * Retorna do cache se ainda válido, ou renova automaticamente.
     * Usa lock de cache para evitar múltiplas renovações simultâneas.
     *
     * @param  string|null  $accountId  ID da conta digital (Hub de Contas)
     */
    public function getToken(?string $accountId = null): AccessToken
    {
        $cacheKey = $accountId
            ? self::CACHE_KEY . ':' . $accountId
            : self::CACHE_KEY;

        /** @var AccessToken|null $cached */
        $cached = Cache::store($this->config['cache_store'] ?? null)->get($cacheKey);

        if ($cached instanceof AccessToken && $cached->isValid()) {
            return $cached;
        }

        // Lock para evitar renovação concorrente
        $lock = Cache::lock(self::CACHE_LOCK_KEY, 10);

        if ($lock->get()) {
            try {
                // Double-check após adquirir o lock
                $cached = Cache::store($this->config['cache_store'] ?? null)->get($cacheKey);

                if ($cached instanceof AccessToken && $cached->isValid()) {
                    return $cached;
                }

                $token = $this->requestToken($accountId);

                $ttl = max(60, $token->expiresAt() - time());

                Cache::store($this->config['cache_store'] ?? null)
                    ->put($cacheKey, $token, $ttl);

                return $token;
            } finally {
                $lock->release();
            }
        }

        // Se não conseguiu o lock, espera e tenta novamente
        usleep(100_000); // 100ms

        $cached = Cache::store($this->config['cache_store'] ?? null)->get($cacheKey);

        if ($cached instanceof AccessToken && $cached->isValid()) {
            return $cached;
        }

        // Fallback: tenta novamente sem lock
        $token = $this->requestToken($accountId);

        $ttl = max(60, $token->expiresAt() - time());

        Cache::store($this->config['cache_store'] ?? null)
            ->put($cacheKey, $token, $ttl);

        return $token;
    }

    /**
     * Realiza a requisição de token via client_credentials.
     *
     *
     * @throws TransfeeraAuthenticationException
     */
    private function requestToken(?string $accountId = null): AccessToken
    {
        $params = [
            'grant_type' => 'client_credentials',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
        ];

        if ($accountId) {
            $params['scope'] = "account_id:{$accountId}";
        }

        $url = rtrim($this->authBaseUrl, '/') . '/authorization';

        $response = Http::asForm()->post($url, $params);

        if ($response->failed()) {
            throw new TransfeeraAuthenticationException(
                message: 'Falha na autenticação: ' . $response->body(),
                statusCode: $response->status(),
                payload: $response->json(),
            );
        }

        return AccessToken::fromResponse($response->json());
    }

    /**
     * Limpa o token em cache (útil para forçar renovação).
     */
    public function clearCache(?string $accountId = null): void
    {
        $cacheKey = $accountId
            ? self::CACHE_KEY . ':' . $accountId
            : self::CACHE_KEY;

        Cache::store($this->config['cache_store'] ?? null)->forget($cacheKey);
    }
}
