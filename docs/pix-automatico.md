# Pix Automático — Laravel Transfeera

Este documento descreve todos os recursos da API de **Pix Automático** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/endpoints.md
> Guia: https://docs.transfeera.dev/docs/pix-automatico-como-funciona.md
> 
> URLs da API:
> - Sandbox: https://api-sandbox.transfeera.com
> - Produção: https://api.mtls.transfeera.com
> - Autenticação Sandbox: https://login-api-sandbox.transfeera.com/authorization
> - Autenticação Produção: https://login-api.transfeera.com/authorization

---

## Resources Disponíveis

| Resource | Classe | Métodos Principais |
|----------|--------|-------------------|
| **Autorizações** | `AuthorizationResource` | `create()`, `get()`, `list()`, `update()`, `cancel()`, `getCancellation()` |
| **Instruções de Pagamento (Payment Intents)** | `PaymentIntentResource` | `create()`, `get()`, `list()`, `cancel()`, `resendRetry()` |

---

## Autorizações (AuthorizationResource)

O Pix Automático permite que um pagador autorize débitos recorrentes ou variáveis em sua conta, com limite de valor e período definidos.

### Criar Autorização

```php
use FlavioMoreir4\Transfeera\DTOs\AuthorizationDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$authDTO = new AuthorizationDTO(
    payerPixKey: 'pagador@email.com',
    limitValue: 50000,              // R$ 500,00 em centavos
    startDate: '2025-01-01',        // opcional
    endDate: '2025-12-31',          // opcional
    splitPayment: [                 // opcional - split de pagamento
        'percentage' => 10,
        'receiverPixKey' => 'parceiro@email.com',
    ],
);

$auth = Transfeera::pixAutomaticoAuthorizations()->create($authDTO);
// Retorna AuthorizationResponseDTO { id, payerPixKey, limitValue, startDate, endDate, splitPayment, status, createdAt, updatedAt }
```

### Listar Autorizações

```php
$auths = Transfeera::pixAutomaticoAuthorizations()->list([
    'status' => 'active',           // active, cancelled, expired, pending
    'payer_pix_key' => 'pagador@email.com',
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<AuthorizationResponseDTO>
```

### Consultar Autorização

```php
$auth = Transfeera::pixAutomaticoAuthorizations()->get('auth_abc123');
// Retorna AuthorizationResponseDTO
```

### Atualizar Split Payment

```php
$auth = Transfeera::pixAutomaticoAuthorizations()->update('auth_abc123', [
    'split_payment' => [
        'percentage' => 15,
        'receiverPixKey' => 'novo_parceiro@email.com',
    ],
]);
```

### Cancelar Autorização

```php
$auth = Transfeera::pixAutomaticoAuthorizations()->cancel('auth_abc123');
// Retorna AuthorizationResponseDTO com status 'cancelled'
```

### Consultar Cancelamento

```php
$cancellation = Transfeera::pixAutomaticoAuthorizations()->getCancellation('auth_abc123');
// Retorna CancellationResponseDTO { id, status, cancelledAt, reason }
```

---

## Instruções de Pagamento — Payment Intents (PaymentIntentResource)

Uma vez criada a autorização, o recebedor cria "instruções de pagamento" (Payment Intents) para debitar o pagador.

### Criar Payment Intent

```php
use FlavioMoreir4\Transfeera\DTOs\PaymentIntentDTO;

$intentDTO = new PaymentIntentDTO(
    authorizationId: 'auth_abc123',
    value: 15000,               // R$ 150,00 em centavos
    description: 'Mensalidade Janeiro',
    dueDate: '2025-01-15',      // opcional - data de vencimento
);

$intent = Transfeera::pixAutomaticoPaymentIntents()->create($intentDTO);
// Retorna PaymentIntentResponseDTO { id, authorizationId, value, description, dueDate, status, createdAt, updatedAt }
```

### Listar Payment Intents

```php
$intents = Transfeera::pixAutomaticoPaymentIntents()->list([
    'authorization_id' => 'auth_abc123',
    'status' => 'pending',      // pending, completed, failed, cancelled
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<PaymentIntentResponseDTO>
```

### Consultar Payment Intent

```php
$intent = Transfeera::pixAutomaticoPaymentIntents()->get('pi_abc123');
// Retorna PaymentIntentResponseDTO
```

### Cancelar Payment Intent

```php
$intent = Transfeera::pixAutomaticoPaymentIntents()->cancel('pi_abc123');
// Retorna PaymentIntentResponseDTO com status 'cancelled'
```

### Reenviar Tentativa (Retry)

```php
// Se a tentativa falhou, reenviar para nova tentativa
$intent = Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_abc123');
// Retorna PaymentIntentResponseDTO com status 'pending'
```

---

## Fluxo Completo: Pix Automático

```php
use FlavioMoreir4\Transfeera\DTOs\AuthorizationDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentIntentDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;

class PixAutomaticoService
{
    public function configurarAutorizacaoMensal(array $dados): AuthorizationResponseDTO
    {
        try {
            $authDTO = new AuthorizationDTO(
                payerPixKey: $dados['pagador_pix_key'],
                limitValue: $dados['limite_mensal_centavos'],
                startDate: $dados['data_inicio'] ?? now()->format('Y-m-d'),
                endDate: $dados['data_fim'] ?? now()->addYear()->format('Y-m-d'),
            );

            return Transfeera::pixAutomaticoAuthorizations()->create($authDTO);

        } catch (TransfeeraException $e) {
            Log::error('Erro ao criar autorização Pix Automático', [
                'pagador' => $dados['pagador_pix_key'],
                'erro' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    public function debitarMensalidade(string $authId, int $valorCentavos, string $descricao): PaymentIntentResponseDTO
    {
        try {
            $intentDTO = new PaymentIntentDTO(
                authorizationId: $authId,
                value: $valorCentavos,
                description: $descricao,
                dueDate: now()->addDays(5)->format('Y-m-d'),
            );

            return Transfeera::pixAutomaticoPaymentIntents()->create($intentDTO);

        } catch (TransfeeraException $e) {
            Log::error('Erro ao criar payment intent', [
                'auth_id' => $authId,
                'valor' => $valorCentavos,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

---

## Webhooks de Pix Automático

### Eventos Disponíveis

| Evento | Descrição | Payload Principal |
|--------|-----------|-------------------|
| `pix_automatico.authorization.created` | Autorização criada | `authorization` |
| `pix_automatico.authorization.cancelled` | Autorização cancelada | `authorization` |
| `pix_automatico.payment_intent.created` | Payment intent criado | `payment_intent` |
| `pix_automatico.payment_intent.completed` | Pagamento confirmado | `payment_intent` |
| `pix_automatico.payment_intent.failed` | Pagamento falhou | `payment_intent`, `error` |
| `pix_automatico.payment_intent.cancelled` | Payment intent cancelado | `payment_intent` |
| `pix_automatico.payment_intent.retry_sent` | Retentativa enviada | `payment_intent` |

### Listener Exemplo

```php
// App\Listeners\PixAutomaticoWebhookListener.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class PixAutomaticoWebhookListener
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'pix_automatico') return;

        match ($event->type) {
            'pix_automatico.payment_intent.completed' => $this->pagamentoConfirmado($event->payload),
            'pix_automatico.payment_intent.failed' => $this->pagamentoFalhou($event->payload),
            default => null,
        };
    }

    private function pagamentoConfirmado(array $payload): void
    {
        $intent = $payload['data'] ?? [];
        $intentId = $intent['id'] ?? null;
        $value = $intent['value'] ?? 0;

        Log::info("Pix Automático pago: {$intentId} - R$ " . number_format($value / 100, 2, ',', '.'));

        // Atualizar status da assinatura, emitir nota, etc.
    }

    private function pagamentoFalhou(array $payload): void
    {
        $intent = $payload['data'] ?? [];
        $error = $payload['error'] ?? 'Erro desconhecido';

        Log::error("Pix Automático falhou: {$error}", ['intent' => $intent]);

        // Notificar financeiro, agendar retentativa, etc.
    }
}
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `PixAutomaticoException` | Erros genéricos na API de Pix Automático (outros que 401/422/429) |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/AuthorizationResourceTest.php
test('cria autorizacao pix automatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/automatic-pix/authorizations' => Http::response([
            'id' => 'auth_123',
            'payer_pix_key' => 'pagador@email.com',
            'limit_value' => 50000,
            'status' => 'active',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::pixAutomaticoAuthorizations()->create([
        'payer_pix_key' => 'pagador@email.com',
        'limit_value' => 50000,
    ]);

    expect($result->id)->toBe('auth_123');
    expect($result->payerPixKey)->toBe('pagador@email.com');
    expect($result->limitValue)->toBe(50000);
});

// tests/Feature/PaymentIntentResourceTest.php
test('cria payment intent', function () {
    Http::fake([
        'api-sandbox.transfeera.com/automatic-pix/authorizations/auth_123/payment-intents' => Http::response([
            'id' => 'pi_1',
            'authorization_id' => 'auth_123',
            'value' => 15000,
            'description' => 'Mensalidade',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::pixAutomaticoPaymentIntents()->create('auth_123', [
        'value' => 15000,
        'description' => 'Mensalidade',
    ]);

    expect($result->id)->toBe('pi_1');
    expect($result->value)->toBe(15000);
});
```

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Webhook de retentativa automática | 📋 Planejado | Depende da API Transfeera |
| Split payment dinâmico por intent | 📋 Planejado | API não suporta override por intent |
| Webhook de expiração de autorização | 📋 Planejado | Evento não documentado na API |

---

## Links Úteis

- [Referência API Pix Automático](https://docs.transfeera.dev/docs/pix-automatico-como-funciona.md)
- [Primeiro Pagamento](primeiro-pagamento.md) — Conceitos de lotes/transferências
- [Webhooks](webhooks.md) — Configuração e segurança
- [Tratamento de Erros](erros.md) — Exceptions e retry