# ADR-009: Multi-tenancy via Escopo `accountId`

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

A Transfeera oferece um Hub de Contas onde uma aplicação pode gerenciar múltiplas contas digitais. Cada operação precisa ser feita "em nome de" uma conta específica, com escopo de token adequado.

## Decisão

Implementar multi-tenancy via **`$accountId` opcional** em todos os Resources:

```php
// Sem accountId — opera como conta principal (dono do client_id)
$batches = Transfeera::batches()->list();

// Com accountId — opera em nome de conta digital específica
$batches = Transfeera::batches('acc_123')->list();
```

O `TokenManager` adiciona `scope=account_id:{accountId}` ao token OAuth2:

```php
if ($accountId) {
    $params['scope'] = "account_id:{$accountId}";
}
```

O cache do token também é particionado por `accountId`:
```php
$cacheKey = $accountId
    ? self::CACHE_KEY.':'.$accountId
    : self::CACHE_KEY;
```

## Consequências

**Positivas:**
- API consistente: `$accountId` no último parâmetro de todo Resource
- Cache isolado: tokens de contas diferentes não se misturam
- Escopo seguro: token só tem acesso à conta especificada
- Sem breaking change: `$accountId` é opcional com default null

**Negativas:**
- Cada conta exige um token separado (mais requisições de auth se muitas contas)
- Cache store precisa suportar chaves com cardinalidade alta
- `$accountId` passado em toda chamada — verboso para operações na mesma conta
