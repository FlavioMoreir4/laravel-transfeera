# Hub de Contas — Laravel Transfeera

Este documento descreve os recursos da API de **Hub de Contas** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/endpoints.md
> 
> URLs da API:
> - Sandbox: https://api-sandbox.transfeera.com
> - Produção (mTLS): https://api.mtls.transfeera.com
> - Autenticação Sandbox: https://login-api-sandbox.transfeera.com/authorization
> - Autenticação Produção: https://login-api.transfeera.com/authorization

---

## Resource Disponível

| Resource | Classe | Métodos Principais |
|----------|--------|-------------------|
| **Contas Digitais** | `AccountResource` | `create()`, `get()`, `list()`, `close()` |

---

## Contas Digitais (AccountResource)

O Hub de Contas permite criar e gerenciar contas digitais (sub-contas) dentro da sua conta Transfeera principal. Cada conta digital possui suas próprias chaves Pix, limites e relatórios.

### Criar Conta Digital

```php
use FlavioMoreir4\Transfeera\DTOs\AccountDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$accountDTO = new AccountDTO(
    name: 'Empresa XYZ Ltda',
    document: '11222333444455',    // CNPJ
    email: 'financeiro@xyz.com',
    phone: '11988887777',           // opcional
    tradeName: 'XYZ Store',         // opcional - nome fantasia
);

$account = Transfeera::accounts()->create($accountDTO);
// Retorna AccountResponseDTO { id, name, document, email, phone, tradeName, status, createdAt, updatedAt }
```

### Listar Contas Digitais

```php
$accounts = Transfeera::accounts()->list([
    'status' => 'active',           // active, inactive, closed
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<AccountResponseDTO>
```

### Consultar Conta Digital

```php
$account = Transfeera::accounts()->get('acc_abc123');
// Retorna AccountResponseDTO
```

### Encerrar Conta Digital

```php
$account = Transfeera::accounts()->close('acc_abc123');
// Remove chaves Pix vinculadas e encerra a conta
// Retorna AccountResponseDTO com status 'closed'
```

---

## Operando em Nome de uma Conta Digital

O SDK suporta **multi-tenancy** via `accountId` — todas as chamadas aceitam o parâmetro opcional `$accountId` como último argumento.

```php
// Criar lote na conta digital 'acc_abc123'
$batch = Transfeera::batches('acc_abc123')->create([
    'name' => 'Pagamentos da Conta XYZ',
]);

// Transferir na conta digital
$transfer = Transfeera::transfers('acc_abc123')->create('batch_123', [
    'amount' => 15000,
    'pix_key' => 'fornecedor@email.com',
]);

// Chaves Pix da conta digital
$keys = Transfeera::pixKeys('acc_abc123')->list();

// QR Codes da conta digital
$qr = Transfeera::pixQrCodes('acc_abc123')->createImmediate([
    'key' => 'conta_xyz@empresa.com',
    'value' => 10000,
]);

// Cobranças da conta digital
$charge = Transfeera::charges('acc_abc123')->create([...]);

// Webhooks da conta digital
$webhook = Transfeera::paymentsWebhooks('acc_abc123')->createUrl([
    'url' => 'https://app.com/webhooks/transfeera',
]);
```

### Como Funciona

Quando `$accountId` é informado:

1. O `TokenManager` adiciona `scope=account_id:{accountId}` na requisição de token
2. O token retornado tem escopo restrito àquela conta digital
3. Todas as chamadas subsequentes usam esse token com escopo limitado

```php
// Fluxo interno (TokenManager)
$params = ['grant_type' => 'client_credentials'];
if ($accountId) {
    $params['scope'] = "account_id:{$accountId}";
}
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `AccountException` | Erros na API de Hub de Contas (outros que 401/422/429) |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/AccountResourceTest.php
test('cria conta digital', function () {
    Http::fake([
        'api-sandbox.transfeera.com/account' => Http::response([
            'id' => 'acc_123',
            'name' => 'Empresa XYZ',
            'document' => '11222333444455',
            'email' => 'financeiro@xyz.com',
            'status' => 'active',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::accounts()->create([
        'name' => 'Empresa XYZ',
        'document' => '11222333444455',
        'email' => 'financeiro@xyz.com',
    ]);

    expect($result->id)->toBe('acc_123');
    expect($result->name)->toBe('Empresa XYZ');
});

test('lista contas digitais', function () {
    Http::fake([
        'api-sandbox.transfeera.com/account*' => Http::response([
            'data' => [
                ['id' => 'acc_1', 'name' => 'Conta 1'],
                ['id' => 'acc_2', 'name' => 'Conta 2'],
            ],
            'meta' => ['current_page' => 1, 'total' => 2],
        ]),
    ]);

    $result = Transfeera::accounts()->list(['page' => 1, 'per_page' => 10]);
    expect($result)->toHaveCount(2);
});

test('encerra conta digital', function () {
    Http::fake([
        'api-sandbox.transfeera.com/account/acc_123' => Http::response([], 204),
    ]);

    $result = Transfeera::accounts()->close('acc_123');
    expect($result)->toBe([]);
});
```

---

## Exemplo Completo: Onboarding de Loja

```php
use FlavioMoreir4\Transfeera\DTOs\AccountDTO;
use FlavioMoreir4\Transfeera\DTOs\PixKeyDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\AccountException;

class LojaOnboardingService
{
    public function onboarding(array $dadosLoja): array
    {
        try {
            // 1. Criar conta digital
            $account = Transfeera::accounts()->create(new AccountDTO(
                name: $dadosLoja['razao_social'],
                document: $dadosLoja['cnpj'],
                email: $dadosLoja['email_financeiro'],
                phone: $dadosLoja['telefone'] ?? null,
                tradeName: $dadosLoja['nome_fantasia'] ?? null,
            ));

            $accountId = $account->id;

            // 2. Criar chave Pix principal (email financeiro)
            $pixKey = Transfeera::pixKeys($accountId)->create(new PixKeyDTO(
                type: 'email',
                value: $dadosLoja['email_financeiro'],
            ));

            // 3. Configurar webhook específico para a conta
            Transfeera::paymentsWebhooks($accountId)->createUrl([
                'url' => config('app.url') . "/webhooks/transfeera/loja/{$accountId}",
            ]);

            Transfeera::receivablesWebhooks($accountId)->createUrl([
                'url' => config('app.url') . "/webhooks/transfeera/loja/{$accountId}/recebimentos",
            ]);

            return [
                'success' => true,
                'account_id' => $accountId,
                'pix_key_id' => $pixKey->id,
                'message' => 'Loja onboarded com sucesso',
            ];

        } catch (AccountException $e) {
            Log::error('Falha no onboarding de loja', [
                'cnpj' => $dadosLoja['cnpj'],
                'erro' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
    }
}
```

---

## Webhooks Específicos por Conta

As URLs de webhook registradas com `accountId` recebem apenas eventos daquela conta digital.

```php
// Registrar webhook para conta específica
Transfeera::paymentsWebhooks('acc_abc123')->createUrl([
    'url' => 'https://app.com/webhooks/transfeera/payments/conta-abc',
]);

Transfeera::receivablesWebhooks('acc_abc123')->createUrl([
    'url' => 'https://app.com/webhooks/transfeera/recebimentos/conta-abc',
]);
```

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Transferência entre contas digitais | 📋 Planejado | Endpoint não documentado na API |
| Relatório consolidado multi-conta | 📋 Planejado | Feature futura |
| Webhook de status da conta (active/inactive) | 📋 Planejado | Evento não documentado na API |

---

## Links Úteis

- [Referência API Hub de Contas](https://docs.transfeera.dev/reference/endpoints.md)
- [Primeiro Pagamento](primeiro-pagamento.md) — Conceitos base
- [Webhooks](webhooks.md) — Configuração por conta
- [Tratamento de Erros](erros.md) — Exceptions e retry