# Conta Certa / Validações — Laravel Transfeera

Este documento descreve os recursos da API de **Conta Certa / Validações** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/tag/Conta-Certa

---

## Resources Disponíveis

| Resource | Classe | Métodos Principais |
|----------|--------|-------------------|
| **Validações** | `ValidationResource` | `create()`, `get()`, `list()` |
| **Bancos** | `BankResource` | `list()` |

---

## Validações de Conta Bancária (ValidationResource)

A API de Conta Certa permite validar se uma conta bancária existe e pertence ao documento informado, antes de realizar pagamentos.

### Criar Validação

```php
use FlavioMoreir4\Transfeera\DTOs\ValidationDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$validationDTO = new ValidationDTO(
    bankCode: '341',              // Código do banco (ex: 341 = Itaú)
    agency: '1234',               // Agência
    account: '56789',             // Conta
    document: '12345678909',      // CPF ou CNPJ
    accountType: 'checking',      // checking (conta corrente) ou savings (poupança)
);

$validation = Transfeera::contaCertaValidations()->create($validationDTO);
// Retorna ValidationResponseDTO { id, bankCode, agency, account, document, accountType, status, createdAt, updatedAt }
```

### Listar Validações

```php
$validations = Transfeera::contaCertaValidations()->list([
    'status' => 'completed',      // pending, completed, failed
    'bank_code' => '341',
    'document' => '12345678909',
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<ValidationResponseDTO>
```

### Consultar Validação

```php
$validation = Transfeera::contaCertaValidations()->get('val_abc123');
// Retorna ValidationResponseDTO { id, bankCode, agency, account, document, accountType, status, createdAt, updatedAt }
```

---

## Bancos Suportados (BankResource - Conta Certa)

```php
$banks = Transfeera::contaCertaBanks()->list();
// Retorna array<BankResponseDTO> { id, name, code (ISPB), status }
```

> **Nota**: Este endpoint retorna apenas os bancos suportados pela API de Conta Certa, que pode ser um subconjunto dos bancos disponíveis para pagamentos.

---

## DTOs

### ValidationDTO (Request)

```php
use FlavioMoreir4\Transfeera\DTOs\ValidationDTO;

$dto = new ValidationDTO(
    bankCode: '341',        // Código COMPE/ISPB do banco
    agency: '1234',         // Agência (sem dígito)
    account: '56789',       // Conta (sem dígito)
    document: '12345678909', // CPF (11 dígitos) ou CNPJ (14 dígitos)
    accountType: 'checking', // 'checking' | 'savings'
);
```

### ValidationResponseDTO (Response)

```php
// Retornado por create(), get(), list()
$validation->id;           // ID da validação
$validation->bankCode;     // Código do banco
$validation->agency;       // Agência
$validation->account;      // Conta
$validation->document;     // Documento validado
$validation->accountType;  // checking | savings
$validation->status;       // pending, completed, failed
$validation->createdAt;
$validation->updatedAt;
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `ContaCertaException` | Erros genéricos na API de Conta Certa |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/ContaCertaValidationTest.php
test('cria validacao de conta', function () {
    Http::fake([
        'api-sandbox.transfeera.com/conta-certa/validations' => Http::response([
            'id' => 'val_123',
            'bank_code' => '341',
            'agency' => '1234',
            'account' => '56789',
            'document' => '12345678909',
            'account_type' => 'checking',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::contaCertaValidations()->create([
        'bank_code' => '341',
        'agency' => '1234',
        'account' => '56789',
        'document' => '12345678909',
        'account_type' => 'checking',
    ]);

    expect($result->id)->toBe('val_123');
    expect($result->status)->toBe('pending');
});

test('lista validacoes', function () {
    Http::fake([
        'api-sandbox.transfeera.com/conta-certa/validations*' => Http::response([
            'data' => [
                ['id' => 'val_1', 'bank_code' => '341', 'status' => 'completed'],
                ['id' => 'val_2', 'bank_code' => '001', 'status' => 'pending'],
            ],
        ]),
    ]);

    $result = Transfeera::contaCertaValidations()->list(['status' => 'completed']);
    expect($result)->toHaveCount(2);
    expect($result[0]->status)->toBe('completed');
});

test('consulta validacao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/conta-certa/validations/val_123' => Http::response([
            'id' => 'val_123',
            'bank_code' => '341',
            'agency' => '1234',
            'account' => '56789',
            'document' => '12345678909',
            'account_type' => 'checking',
            'status' => 'completed',
        ]),
    ]);

    $result = Transfeera::contaCertaValidations()->get('val_123');
    expect($result->status)->toBe('completed');
});

// tests/Feature/ContaCertaBankResourceTest.php
test('lista bancos conta certa', function () {
    Http::fake([
        'api-sandbox.transfeera.com/conta-certa/banks' => Http::response([
            ['id' => '341', 'name' => 'Itaú', 'code' => '341'],
            ['id' => '001', 'name' => 'Banco do Brasil', 'code' => '001'],
        ]),
    ]);

    $banks = Transfeera::contaCertaBanks()->list();
    expect($banks)->toHaveCount(2);
});
```

---

## Webhooks de Conta Certa

### Rotas

- `POST /webhooks/transfeera/conta-certa`

### Eventos

| Evento | Descrição |
|--------|-----------|
| `validation.created` | Validação iniciada |
| `validation.completed` | Validação concluída (sucesso ou falha) |
| `validation.failed` | Validação falhou |

### Listener Exemplo

```php
// App\Listeners\ContaCertaWebhookListener.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class ContaCertaWebhookListener
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'conta_certa') return;

        match ($event->type) {
            'validation.completed' => $this->validacaoConcluida($event->payload),
            'validation.failed' => $this->validacaoFalhou($event->payload),
            default => null,
        };
    }

    private function validacaoConcluida(array $payload): void
    {
        $validation = $payload['data'] ?? [];
        $status = $validation['status'] ?? 'unknown';

        Log::info("Validação Conta Certa: {$status}", ['validation' => $validation]);

        if ($status === 'completed') {
            // Conta válida - prosseguir com pagamento
        } else {
            // Conta inválida - notificar usuário
        }
    }
}
```

---

## Configuração no `.env`

```env
# Opcional - secrets por domínio
TRANSFEERA_WEBHOOK_SECRET_CONTA_CERTA=secret_contacerta_forte
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `ContaCertaException` | Erros genéricos na API de Conta Certa (outros que 401/422/429) |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Validação em lote (batch) | 📋 Planejado | API não suporta batch nativo |
| Webhook de validação expirada | 📋 Planejado | Evento não documentado na API |

---

## Links Úteis

- [Referência API Conta Certa](https://docs.transfeera.dev/reference/tag/Conta-Certa)
- [Primeiro Pagamento](primeiro-pagamento.md) — Pagamentos que usam validação
- [Webhooks](webhooks.md) — Configuração e segurança
- [Tratamento de Erros](erros.md) — Exceptions e retry