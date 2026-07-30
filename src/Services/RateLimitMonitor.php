<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Monitora o consumo de rate limit da API Transfeera em tempo real.
 *
 * Captura os headers X-RateLimit-Limit, X-RateLimit-Remaining e
 * X-RateLimit-Reset de TODAS as respostas (não apenas 429),
 * permitindo que a aplicação consulte o estado atual antes de
 * tomar decisões de roteamento ou backoff.
 *
 * Os dados são armazenados em cache com TTL curto (60s) para
 * sobreviver ao ciclo da request sem depender de singletons.
 */
class RateLimitMonitor
{
    private const string CACHE_PREFIX = 'transfeera_rate_limit_';

    /**
     * Atualiza o estado de rate limit a partir dos headers de uma resposta.
     *
     * @param  string  $domain  Domínio da API (Connector::DOMAIN_*)
     * @param  Response  $response  Resposta HTTP (bem-sucedida ou erro)
     */
    public function updateFromResponse(string $domain, Response $response): void
    {
        $remaining = $this->parseIntHeader($response, 'X-RateLimit-Remaining');
        $limit = $this->parseIntHeader($response, 'X-RateLimit-Limit');
        $reset = $this->parseIntHeader($response, 'X-RateLimit-Reset');

        if ($remaining === null && $limit === null && $reset === null) {
            return;
        }

        Cache::put(
            $this->cacheKey($domain),
            [
                'remaining' => $remaining,
                'limit' => $limit,
                'reset' => $reset,
                'updated_at' => time(),
            ],
            60, // TTL curto — sempre atualizado nas próximas requests
        );
    }

    /**
     * Retorna quantas requisições ainda podem ser feitas no domínio.
     * Retorna null se o rate limit nunca foi reportado.
     */
    public function getRemaining(string $domain): ?int
    {
        $state = $this->getState($domain);

        return $state['remaining'] ?? null;
    }

    /**
     * Retorna o limite total de requisições na janela atual.
     */
    public function getLimit(string $domain): ?int
    {
        $state = $this->getState($domain);

        return $state['limit'] ?? null;
    }

    /**
     * Retorna o timestamp Unix de quando o rate limit será resetado.
     */
    public function getReset(string $domain): ?int
    {
        $state = $this->getState($domain);

        return $state['reset'] ?? null;
    }

    /**
     * Retorna o timestamp Unix da última atualização.
     */
    public function getLastUpdated(string $domain): ?int
    {
        $state = $this->getState($domain);

        return $state['updated_at'] ?? null;
    }

    /**
     * Verifica se o domínio está próximo do rate limit (<= 10% restantes).
     */
    public function isThrottled(string $domain, float $threshold = 0.1): bool
    {
        $state = $this->getState($domain);

        if ($state['remaining'] === null || $state['limit'] === null || $state['limit'] === 0) {
            return false;
        }

        return $state['remaining'] <= max(1, (int) ($state['limit'] * $threshold));
    }

    /**
     * Retorna o estado completo do rate limit para o domínio.
     *
     * @return array{remaining: int|null, limit: int|null, reset: int|null, updated_at: int|null}
     */
    public function getState(string $domain): array
    {
        /** @var array{remaining: int|null, limit: int|null, reset: int|null, updated_at: int|null}|null $cached */
        $cached = Cache::get($this->cacheKey($domain));

        if (! is_array($cached)) {
            return [
                'remaining' => null,
                'limit' => null,
                'reset' => null,
                'updated_at' => null,
            ];
        }

        return $cached;
    }

    /**
     * Limpa o estado armazenado para um domínio.
     */
    public function clear(string $domain): void
    {
        Cache::forget($this->cacheKey($domain));
    }

    /**
     * Limpa o estado de todos os domínios.
     */
    public function clearAll(): void
    {
        $domains = ['payments', 'receivables', 'pix_automatico', 'accounts', 'infractions', 'conta_certa'];

        foreach ($domains as $domain) {
            $this->clear($domain);
        }
    }

    private function cacheKey(string $domain): string
    {
        return self::CACHE_PREFIX.$domain;
    }

    private function parseIntHeader(Response $response, string $name): ?int
    {
        $value = $response->header($name);

        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }
}
