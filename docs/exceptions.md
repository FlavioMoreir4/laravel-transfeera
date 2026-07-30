# Exceptions — Laravel Transfeera

Este documento descreve a hierarquia completa de exceptions do SDK, quando cada uma é lançada, e como tratá-las.

> Para exemplos de uso em Controllers, Jobs, Handlers globais, veja [Tratamento de Erros](erros.md).

---

## Hierarquia Completa

```
TransfeeraException (base)
├── TransfeeraAuthenticationException      → HTTP 401
├── TransfeeraValidationException          → HTTP 422
├── TransfeeraRateLimitException           → HTTP 429
├── TransfeeraNotFoundException            → HTTP 404
├── TransfeeraConflictException            → HTTP 409
├── TransfeeraMismatchException            → HTTP 400
├── TransfeeraServerException              → HTTP 5xx
├── TransfeeraMtlException                 → mTLS errors
│
├── PaymentException                       → API Pagamentos
├── ReceivableException                    → API Recebimentos
├── PixAutomaticoException                 → API Pix Automático
├── ContaCertaException                    → API Conta Certa
├── AccountException                       → API Hub de Contas
└── InfractionException                    → API MED/Infrações
```

---

## Exceptions Base (HTTP Específicos)

### TransfeeraException (Base)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraException $e) {
    $e->getMessage();           // Mensagem de erro
    $e->getCode();              // Código HTTP (ou 0 se erro de rede)
    $e->getStatusCode();        // Alias de getCode()
    $e->getPayload();           // Array|null — payload bruto da resposta
    $e->getPrevious();          // Exception anterior (se chain)
}
```

---

### TransfeeraAuthenticationException (HTTP 401)

Lançada quando:
- Credenciais inválidas (Client ID/Secret)
- Token expirado e falha ao renovar
- Scope insuficiente (ex: precisa `account_id`)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;

try {
    Transfeera::batches()->list();
} catch (TransfeeraAuthenticationException $e) {
    // Verificar .env: TRANSFEERA_CLIENT_ID, TRANSFEERA_CLIENT_SECRET
    // Token pode ter expirado e renovação falhou
    Log::error('Auth falhou', ['msg' => $e->getMessage()]);
}
```

---

### TransfeeraValidationException (HTTP 422)

Lançada quando a API retorna 422 Unprocessable Entity — dados inválidos.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;

try {
    Transfeera::charges()->create(new ChargeDTO(
        payerName: '',
        value: -100,
        dueDate: 'invalid',
    ));
} catch (TransfeeraValidationException $e) {
    $e->getErrors();           // array<string, string[]> — campo => mensagens
    $e->getFirstError();       // string — primeira mensagem
    $e->hasError('field_name'); // bool

    // Exemplo de $e->getErrors():
    // [
    //     'payer_name' => ['O nome do pagador é obrigatório'],
    //     'value' => ['O valor deve ser maior que zero'],
    //     'due_date' => ['Formato de data inválido (use Y-m-d)'],
    // ]
}
```

---

### TransfeeraRateLimitException (HTTP 429)

Lançada quando a API retorna 429 Too Many Requests.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraRateLimitException $e) {
    $retryAfter = $e->getRetryAfter(); // int|null — segundos do header Retry-After

    // Implementar backoff exponencial
    $waitTime = $retryAfter ?? 60;

    // Em Job com backoff:
    throw $e; // Laravel Queue fará retry com backoff se configurado
}
```

---

### TransfeeraNotFoundException (HTTP 404)

Lançada quando o recurso não existe.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraNotFoundException;

try {
    Transfeera::batches()->get('batch_inexistente');
} catch (TransfeeraNotFoundException $e) {
    $e->getResourceType(); // 'batch', 'transfer', 'charge', etc.
    $e->getResourceId();   // ID que não foi encontrado
}
```

---

### TransfeeraConflictException (HTTP 409)

Lançada quando há conflito (ex: chave Pix já cadastrada, lote já processado).

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraConflictException;

try {
    Transfeera::pixKeys()->create(new PixKeyDTO(type: 'cpf', value: '12345678909'));
} catch (TransfeeraConflictException $e) {
    // Chave Pix já existe nessa ou outra instituição
}
```

---

### TransfeeraMismatchException (HTTP 400)

Lançada quando o payload está malformado (JSON inválido, Content-Type errado, etc).

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraMismatchException;

try {
    Transfeera::batches()->create('invalid json');
} catch (TransfeeraMismatchException $e) {
    // Payload malformado
}
```

---

### TransfeeraServerException (HTTP 5xx)

Lançada para erros 500, 502, 503, 504 da Transfeera.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraServerException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraServerException $e) {
    // Erro interno da Transfeera — implementar retry com backoff
    Log::critical('Transfeera indisponível', ['status' => $e->getStatusCode()]);
}
```

---

### TransfeeraMtlException (mTLS)

Lançada quando há erro de configuração de mTLS em produção.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraMtlException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraMtlException $e) {
    // Certificado não encontrado, inválido, ou chave não corresponde
    // Verificar TRANSFEERA_MTLS_CERT_PATH e TRANSFEERA_MTLS_KEY_PATH
}
```

---

## Exceptions por Domínio (HTTP ≠ 401/422/429)

Lançadas para outros códigos HTTP (ex: 400, 403, 500) agrupados por domínio da API.

### PaymentException — API Pagamentos

```php
use FlavioMoreir4\Transfeera\Exceptions\PaymentException;

try {
    Transfeera::batches()->create([...]);
} catch (PaymentException $e) {
    // Erro 400, 403, 500, etc. na API de pagamentos
    $e->getPayload(); // Payload bruto da resposta
}
```

### ReceivableException — API Recebimentos

```php
use FlavioMoreir4\Transfeera\Exceptions\ReceivableException;

try {
    Transfeera::charges()->create([...]);
} catch (ReceivableException $e) {
    // Erro na API de recebimentos
}
```

### PixAutomaticoException — API Pix Automático

```php
use FlavioMoreir4\Transfeera\Exceptions\PixAutomaticoException;

try {
    Transfeera::pixAutomaticoAuthorizations()->create([...]);
} catch (PixAutomaticoException $e) {
    // Erro na API de Pix Automático
}
```

### ContaCertaException — API Conta Certa

```php
use FlavioMoreir4\Transfeera\Exceptions\ContaCertaException;

try {
    Transfeera::contaCertaValidations()->create([...]);
} catch (ContaCertaException $e) {
    // Erro na API de Conta Certa
}
```

### AccountException — API Hub de Contas

```php
use FlavioMoreir4\Transfeera\Exceptions\AccountException;

try {
    Transfeera::accounts()->create([...]);
} catch (AccountException $e) {
    // Erro na API de Hub de Contas
}
```

### InfractionException — API MED/Infrações

```php
use FlavioMoreir4\Transfeera\Exceptions\InfractionException;

try {
    Transfeera::infractions()->submitAnalysis('inf_123', [...]);
} catch (InfractionException $e) {
    // Erro na API de MED/Infrações
}
```

---

## Métodos Comuns (Todas Herdam de TransfeeraException)

| Método | Retorno | Descrição |
|--------|---------|-----------|
| `getMessage()` | string | Mensagem de erro legível |
| `getCode()` | int | Código HTTP (ou 0) |
| `getStatusCode()` | int | Alias de getCode() |
| `getPayload()` | array\|null | Resposta bruta da API |
| `getPrevious()` | Throwable\|null | Exception anterior na chain |
| `fromResponse(array $data, int $status): static` | static | Factory a partir de resposta da API |

---

## Uso Recomendado

### 1. Catch Específico → Geral (Ordem Importante!)

```php
try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraValidationException $e) {
    // 422 — dados inválidos
    return response()->json(['errors' => $e->getErrors()], 422);
} catch (TransfeeraAuthenticationException $e) {
    // 401 — credenciais
    return response()->json(['error' => 'unauthorized'], 401);
} catch (TransfeeraRateLimitException $e) {
    // 429 — rate limit
    return response()->json(['error' => 'rate_limit'], 429)
        ->header('Retry-After', $e->getRetryAfter() ?? 60);
} catch (PaymentException $e) {
    // Outros erros da API de pagamentos
    return response()->json(['error' => $e->getMessage()], $e->getStatusCode() ?: 500);
} catch (TransfeeraException $e) {
    // Fallback genérico
    return response()->json(['error' => 'transfeera_error'], 500);
}
```

### 2. Handler Global (Laravel)

Veja [Tratamento de Erros](erros.md#3-middleware-global-de-tratamento-laravel) para configuração completa no `app/Exceptions/Handler.php`.

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Unit/ConnectorErrorMappingTest.php
test('mapeia erro 401 para AuthenticationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Unauthenticated'], 401),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batch'))
        ->toThrow(TransfeeraAuthenticationException::class);
});

test('mapeia erro 422 para ValidationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['name' => ['Obrigatório']],
        ], 422),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batch'))
        ->toThrow(TransfeeraValidationException::class);
});

test('mapeia erro 429 para RateLimitException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Too Many Requests'], 429, [
            'Retry-After' => '60',
        ]),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batch'))
        ->toThrow(TransfeeraRateLimitException::class);
});
```

---

## Referência Rápida: Qual Exception Catchar?

| Situação | Exception Específica | Exception Base (catch-all) |
|----------|---------------------|---------------------------|
| Dados inválidos (422) | `TransfeeraValidationException` | `TransfeeraException` |
| Credenciais/token (401) | `TransfeeraAuthenticationException` | `TransfeeraException` |
| Rate limit (429) | `TransfeeraRateLimitException` | `TransfeeraException` |
| Recurso não encontrado (404) | `TransfeeraNotFoundException` | `TransfeeraException` |
| Conflito (409) | `TransfeeraConflictException` | `TransfeeraException` |
| Payload malformado (400) | `TransfeeraMismatchException` | `TransfeeraException` |
| Erro servidor Transfeera (5xx) | `TransfeeraServerException` | `TransfeeraException` |
| mTLS config | `TransfeeraMtlException` | `TransfeeraException` |
| **API Pagamentos** (outros) | `PaymentException` | `TransfeeraException` |
| **API Recebimentos** (outros) | `ReceivableException` | `TransfeeraException` |
| **API Pix Automático** (outros) | `PixAutomaticoException` | `TransfeeraException` |
| **API Conta Certa** (outros) | `ContaCertaException` | `TransfeeraException` |
| **API Hub Contas** (outros) | `AccountException` | `TransfeeraException` |
| **API MED** (outros) | `InfractionException` | `TransfeeraException` |

---

## Links Úteis

- [Tratamento de Erros](erros.md) — Handlers globais, Jobs, retry, debug
- [Primeiro Pagamento](primeiro-pagamento.md) — Exemplo com try/catch
- [Referência API Erros](https://docs.transfeera.dev/reference/erros) — Códigos oficiais da Transfeera