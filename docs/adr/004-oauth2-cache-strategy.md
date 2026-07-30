# ADR-004: OAuth2 Client Credentials com Cache + Lock

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

A API Transfeera usa OAuth2 com fluxo `client_credentials`. O token de acesso tem validade limitada (`expires_in`) e precisa ser renovado periodicamente. O SDK precisa:

1. Obter token automaticamente antes de cada requisição
2. Cachear para não renovar a cada request (performance)
3. Renovar antes da expiração real (margem de segurança)
4. Evitar N renovações concorrentes (N requests expirados simultâneos)

## Decisão

Implementar `TokenManager` com:

1. **Cache do Laravel** — Armazena o token após obtê-lo. Store configurável via `config('transfeera.cache_store')`.
2. **Margem de segurança de 60s** — Token é renovado 60s antes do `expires_in` real.
3. **Lock de cache** — `Cache::lock()` com TTL de 10s impede múltiplas renovações simultâneas.
4. **Double-check** — Após adquirir o lock, verifica novamente se outro processo já renovou.
5. **Fallback sem lock** — Se não conseguiu o lock e o cache ainda está vazio, tenta renovar mesmo assim.

```php
public function getToken(?string $accountId = null): AccessToken
{
    $cacheKey = $accountId
        ? self::CACHE_KEY.':'.$accountId
        : self::CACHE_KEY;

    $cached = Cache::store($this->config['cache_store'] ?? null)->get($cacheKey);

    if ($cached instanceof AccessToken && $cached->isValid()) {
        return $cached;                                   // Cache hit
    }

    $lock = Cache::lock(self::CACHE_LOCK_KEY, 10);

    if ($lock->get()) {
        try {
            $cached = Cache::store(...)->get($cacheKey);
            if ($cached instanceof AccessToken && $cached->isValid()) {
                return $cached;                            // Double-check hit
            }

            $token = $this->requestToken($accountId);      // Renova
            Cache::store(...)->put($cacheKey, $token, $ttl);

            return $token;
        } finally {
            $lock->release();
        }
    }

    usleep(100_000);  // Espera 100ms
    // Fallback: tenta cache ou request sem lock
}
```

## Consequências

**Positivas:**
- Performance: token é reutilizado até próximo da expiração
- Segurança: lock evita flood no endpoint de autenticação
- Resiliência: fallback funciona mesmo sem lock
- Multi-tenancy: cache separado por `accountId`

**Negativas:**
- Complexidade adicional (lock, double-check, fallback)
- Dependência do `Cache::lock()` que requer store atômica (redis, database)
- Se o cache store não suporta locks, o comportamento degrada para sempre renovar

## Alternativas Consideradas

1. **Sempre renovar antes de cada request** — Rejeitado. Impacto de performance inaceitável.
2. **TTL fixo no cache sem lock** — Rejeitado. Race condition em requests concorrentes expirados.
3. **TokenManager como singleton sem cache** — Rejeitado. Memory leak em processos longos (Octane, RoadRunner).
