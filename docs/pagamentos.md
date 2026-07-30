# Pagamentos — Laravel Transfeera

Este documento descreve todos os recursos da API de **Pagamentos** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/endpoints.md
> Guia: https://docs.transfeera.dev/docs/pagamentos-lotes-como-funciona.md
> 
> URLs da API:
> - Sandbox: https://api-sandbox.transfeera.com
> - Produção (mTLS): https://api.mtls.transfeera.com
> - Autenticação Sandbox: https://login-api-sandbox.transfeera.com/authorization
> - Autenticação Produção: https://login-api.transfeera.com/authorization

---

## Resources Disponíveis

| Resource | Classe | Métodos Principais |
|----------|--------|-------------------|
| **Lotes (Batches)** | `BatchResource` | `create()`, `get()`, `list()`, `update()`, `delete()`, `process()` |
| **Transferências** | `TransferResource` | `create()`, `get()`, `list()`, `update()`, `delete()` |
| **Boletos** | `BilletResource` | `create()`, `createStandalone()`, `get()`, `list()`, `update()`, `delete()` |
| **Bancos** | `BankResource` | `list()` |
| **Saldo/Extrato** | `StatementResource` | `getBalance()`, `requestReport()`, `listReports()` |
| **Pix (Consulta)** | `PixResource` | `lookupKey()`, `parseEmv()` |
| **Recorrências** | `RecurrenceResource` | `list()`, `listPayments()`, `cancel()` |

---

## Lotes (BatchResource)

### Criar Lote

```php
use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$batchDTO = new BatchDTO(
    name: 'Pagamentos Fornecedores Dezembro',
    type: 'immediate', // ou 'scheduled'
    scheduledDate: '2025-12-20' // opcional, só para scheduled
);

$batch = Transfeera::batches()->create($batchDTO);
// Retorna BatchResponseDTO { id, name, status, type, scheduledDate, createdAt, updatedAt }
```

### Listar Lotes

```php
$batches = Transfeera::batches()->list([
    'page' => 1,
    'per_page' => 20,
    'status' => 'pending', // pending, processing, processed, canceled
    'start_date' => '2025-12-01',
    'end_date' => '2025-12-31',
]);
// Retorna array<BatchResponseDTO>
```

### Consultar Lote

```php
$batch = Transfeera::batches()->get('batch_abc123');
// Retorna BatchResponseDTO
```

### Atualizar Lote

```php
$batch = Transfeera::batches()->update('batch_abc123', [
    'name' => 'Nome Atualizado',
]);
```

### Processar (Fechar) Lote

```php
$batch = Transfeera::batches()->process('batch_abc123');
// Muda status para 'processing' e inicia pagamentos
```

### Deletar Lote

```php
Transfeera::batches()->delete('batch_abc123');
```

---

## Transferências (TransferResource)

> Transferências pertencem a um lote. O SDK suporta consulta standalone (`/transfer/{id}`) e contextual (`/batch/{batchId}/transfer/{id}`).

### Criar Transferência

```php
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;

$transferDTO = new TransferDTO(
    amount: 15000,                    // R$ 150,00 em centavos
    pixKey: 'fornecedor@email.com',
    pixKeyType: 'email',              // cpf, cnpj, email, phone, evp
    description: 'Pagamento NF #1234', // opcional
);

$transfer = Transfeera::transfers()->create('batch_abc123', $transferDTO);
// Retorna TransferResponseDTO { id, batchId, amount, pixKey, pixKeyType, description, status, createdAt, updatedAt }
```

### Listar Transferências do Lote

```php
$transfers = Transfeera::transfers()->list('batch_abc123', [
    'page' => 1,
    'per_page' => 20,
    'status' => 'pending', // pending, completed, failed, canceled
]);
// Retorna array<TransferResponseDTO>
```

### Consultar Transferência (Standalone)

```php
$transfer = Transfeera::transfers()->get('transfer_xyz789');
// Retorna TransferResponseDTO
```

### Consultar Transferência (Contexto do Lote)

```php
$transfer = Transfeera::transfers()->get('transfer_xyz789', 'batch_abc123');
```

### Atualizar Transferência

```php
$transfer = Transfeera::transfers()->update('batch_abc123', 'transfer_xyz789', [
    'description' => 'Descrição atualizada',
]);
```

### Deletar Transferência

```php
Transfeera::transfers()->delete('batch_abc123', 'transfer_xyz789');
```

---

## Boletos (BilletResource)

> Suporta operações **dentro de lote** e **avulsas (standalone)**.

### Criar Boleto no Lote

```php
use FlavioMoreir4\Transfeera\DTOs\BilletDTO;

$billetDTO = new BilletDTO(
    payerName: 'João Silva',
    value: 50000,                    // R$ 500,00
    dueDate: '2025-12-31',
    document: '12345678909',
    documentType: 'cpf',
    description: 'Fatura Janeiro',
    metadata: ['pedido_id' => 'PED123'],
);

$billet = Transfeera::billets()->create('batch_abc123', $billetDTO);
// Retorna BilletResponseDTO
```

### Criar Boleto Avulso (Standalone)

```php
$billet = Transfeera::billets()->createStandalone($billetDTO);
```

### Listar Boletos do Lote

```php
$billets = Transfeera::billets()->list('batch_abc123', [
    'status' => 'pending', // pending, paid, canceled, expired
]);
```

### Listar Boletos Avulsos

```php
$billets = Transfeera::billets()->listStandalone([
    'status' => 'paid',
    'page' => 1,
    'per_page' => 20,
]);
```

---

## Bancos (BankResource)

```php
$banks = Transfeera::banks()->list();
// Retorna array<BankResponseDTO> { id, name, code, status }
```

---

## Saldo e Extrato (StatementResource)

### Consultar Saldo

```php
$balance = Transfeera::statement()->getBalance();
// Retorna array { available, blocked, pending }
```

### Solicitar Relatório de Extrato

```php
use FlavioMoreir4\Transfeera\DTOs\StatementReportDTO;

$reportDTO = new StatementReportDTO(
    startDate: '2025-01-01',
    endDate: '2025-01-31',
);

$report = Transfeera::statement()->requestReport($reportDTO);
// Retorna StatementReportResponseDTO { id, status, url, createdAt }
```

### Listar Relatórios Solicitados

```php
$reports = Transfeera::statement()->listReports([
    'status' => 'completed', // pending, completed, failed
]);
```

---

## Pix — Consulta (PixResource)

### Consultar Chave Pix (DICT)

```php
$pixData = Transfeera::pix()->lookupKey('fornecedor@email.com');
// Retorna array { key, type, name, city, bank }
```

### Parsear EMV

```php
$parsed = Transfeera::pix()->parseEmv('00020126580014BR.GOV.BCB.PIX...');
// Retorna array { payload, crc, key, amount, etc }
```

---

## Recorrências (RecurrenceResource)

```php
$recurrences = Transfeera::recurrences()->list([
    'status' => 'active', // active, canceled, finished
]);

$payments = Transfeera::recurrences()->listPayments('rec_abc123', [
    'status' => 'completed',
]);

Transfeera::recurrences()->cancel('rec_abc123');
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `PaymentException` | Erros genéricos na API de pagamentos (outros que 401/422/429) |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/BatchResourceTest.php
test('cria lote com sucesso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response([
            'id' => 'batch_123',
            'name' => 'Pagamentos Fornecedores',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::batches()->create(['name' => 'Pagamentos Fornecedores']);
    expect($result->id)->toBe('batch_123');
    expect($result->name)->toBe('Pagamentos Fornecedores');
});

// tests/Feature/TransferResourceTest.php
test('cria transferencia em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/transfer' => Http::response([
            'id' => 'transfer_1',
            'batch_id' => 'batch_123',
            'amount' => 15000,
            'pix_key' => 'fulano@email.com',
            'pix_key_type' => 'email',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::transfers()->create('batch_123', [
        'amount' => 15000,
        'pix_key' => 'fulano@email.com',
    ]);
    expect($result->id)->toBe('transfer_1');
});
```

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Agendamento de transferências futuras | 📋 Planejado | Depende da API Transfeera |
| Bulk create de transferências | 📋 Planejado | API não suporta bulk nativo |
| Cancelamento em massa de lote | 📋 Planejado | Requer endpoint batch |

---

## Links Úteis

- [Referência API Pagamentos](https://docs.transfeera.dev/reference/endpoints.md)
- [Primeiro Pagamento](primeiro-pagamento.md) — Guia passo a passo
- [Webhooks](webhooks.md) — Eventos de pagamento
- [Tratamento de Erros](erros.md) — Exceptions e retry