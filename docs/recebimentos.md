# Recebimentos — Laravel Transfeera

Este documento descreve todos os recursos da API de **Recebimentos** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/endpoints.md
> Guia: https://docs.transfeera.dev/docs/recebimentos-introducao.md
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
| **Chaves Pix** | `PixKeyResource` | `create()`, `get()`, `list()`, `verify()`, `delete()`, `claim()`, `confirmClaim()`, `cancelClaim()` |
| **QR Codes Pix** | `PixQrCodeResource` | `createStatic()`, `createImmediate()`, `createDue()`, `get()`, `list()`, `revoke()` |
| **Pix Recebidos (Cash-in)** | `PixCashInResource` | `list()`, `getByEnd2EndId()`, `requestRefund()`, `getRefunds()` |
| **Cobranças (Boleto + Pix)** | `ChargeResource` | `create()`, `get()`, `list()`, `cancel()`, `downloadPdf()` |
| **Links de Pagamento** | `PaymentLinkResource` | `create()`, `get()`, `list()`, `delete()` |

---

## Chaves Pix (PixKeyResource)

### Criar Chave Pix

```php
use FlavioMoreir4\Transfeera\DTOs\PixKeyDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$pixKeyDTO = new PixKeyDTO(
    type: 'email',           // cpf, cnpj, email, phone, evp
    value: 'financeiro@empresa.com',
);

$pixKey = Transfeera::pixKeys()->create($pixKeyDTO);
// Retorna PixKeyResponseDTO { id, type, value, status, claimedAt, createdAt, updatedAt }
```

### Listar Chaves Pix

```php
$keys = Transfeera::pixKeys()->list([
    'status' => 'active',    // active, inactive, claimed
    'type' => 'email',
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<PixKeyResponseDTO>
```

### Consultar Chave Pix

```php
$pixKey = Transfeera::pixKeys()->get('key_abc123');
// Retorna PixKeyResponseDTO
```

### Verificar Chave (Código de Verificação)

```php
Transfeera::pixKeys()->verify('key_abc123', '123456');
// Retorna array { verified: true, claimedAt: '...' }
```

### Deletar Chave Pix

```php
Transfeera::pixKeys()->delete('key_abc123');
```

### Portabilidade — Reivindicar Chave (Claim)

```php
// Solicitar portabilidade de chave de outra instituição
$claim = Transfeera::pixKeys()->claim('11999999999'); // telefone

// Confirmar portabilidade (após receber código no app do banco)
Transfeera::pixKeys()->confirmClaim($claim->id);

// Cancelar solicitação
Transfeera::pixKeys()->cancelClaim($claim->id);
```

---

## QR Codes Pix (PixQrCodeResource)

### QR Code Estático (Valor Definido pelo Pagador)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeStaticDTO;

$dto = new PixQrCodeStaticDTO(
    key: 'financeiro@empresa.com',
    value: null,              // null = pagador define valor
    description: 'Doação',
    additionalData: 'REF123', // opcional
);

$qrStatic = Transfeera::pixQrCodes()->createStatic($dto);
// Retorna PixQrCodeResponseDTO { id, key, type, value, emv, imageUrl, createdAt }
```

### QR Code Imediato (Valor Fixo, Expiração Curta ~1h)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeImmediateDTO;

$dto = new PixQrCodeImmediateDTO(
    key: 'financeiro@empresa.com',
    value: 25000,             // R$ 250,00 em centavos
    description: 'Produto X',
    additionalData: 'PEDIDO123',
);

$qrImmediate = Transfeera::pixQrCodes()->createImmediate($dto);
// Expira em ~1 hora (configurável na Transfeera)
```

### QR Code com Vencimento (Boleto Híbrido)

```php
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeDueDTO;

$dto = new PixQrCodeDueDTO(
    key: 'financeiro@empresa.com',
    value: 15000,             // R$ 150,00
    dueDate: '2025-12-31',    // Data vencimento
    description: 'Fatura Janeiro',
    additionalData: 'FAT202501',
);

$qrDue = Transfeera::pixQrCodes()->createDue($dto);
// Funciona como boleto: vence na data, pode pagar via Pix ou código de barras
```

### Listar QR Codes

```php
$qrCodes = Transfeera::pixQrCodes()->list([
    'type' => 'immediate',    // static, immediate, due
    'status' => 'active',     // active, revoked, expired
    'page' => 1,
    'per_page' => 20,
]);
```

### Revogar QR Code

```php
Transfeera::pixQrCodes()->revoke('qr_abc123');
```

---

## Pix Recebidos — Cash-in (PixCashInResource)

### Listar Pix Recebidos

```php
$pixList = Transfeera::pixCashIn()->list([
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'status' => 'completed',  // completed, returned
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<PixCashInResponseDTO>
```

### Consultar por End2EndId (Único do Pix)

```php
$pix = Transfeera::pixCashIn()->getByEnd2EndId('E2E123456789');
// Retorna PixCashInResponseDTO
```

### Solicitar Devolução

```php
$refund = Transfeera::pixCashIn()->requestRefund('E2E123456789', [
    'amount' => 5000, // Valor parcial em centavos (opcional, total se omitido)
]);
// Retorna RefundResponseDTO
```

### Consultar Devoluções de um Cash-in

```php
$refunds = Transfeera::pixCashIn()->getRefunds('E2E123456789');
// Retorna array<RefundResponseDTO>
```

---

## Cobranças — Boleto + Pix (ChargeResource)

> A cobrança cria **boleto + QR Code Pix** em uma única chamada. O cliente escolhe como pagar.

### Criar Cobrança

```php
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;

$chargeDTO = new ChargeDTO(
    payerName: 'João Silva',
    value: 15000,                 // R$ 150,00
    payerDocument: '12345678909', // CPF
    dueDate: '2025-12-31',        // Vencimento
    metadata: ['pedido_id' => 'PED123'],
);

$charge = Transfeera::charges()->create($chargeDTO);
// Retorna ChargeResponseDTO com:
// - boleto: barCode, digitableLine, pdfUrl
// - pix: qrCode (emv, imageUrl), end2endId
```

### Listar Cobranças

```php
$charges = Transfeera::charges()->list([
    'status' => 'pending',        // pending, completed, canceled, expired
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<ChargeResponseDTO>
```

### Consultar Cobrança

```php
$charge = Transfeera::charges()->get('chg_abc123');
// Retorna ChargeResponseDTO
```

### Cancelar Cobrança

```php
Transfeera::charges()->cancel('chg_abc123');
```

### Baixar Comprovante (PDF)

```php
// Precisa do receivable_id do recebível (retornado na cobrança)
$pdf = Transfeera::charges()->downloadPdf('chg_abc123', 'rec_xyz123');
// Retorna string binária do PDF
header('Content-Type: application/pdf');
echo $pdf;
```

---

## Links de Pagamento (PaymentLinkResource)

### Criar Link de Pagamento

```php
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;

$linkDTO = new PaymentLinkDTO(
    name: 'Produto Premium',
    value: 29900,                 // R$ 299,00
    description: 'Assinatura Anual',
    expiresIn: 30,                // Dias para expirar
    redirectUrl: 'https://meuapp.com/sucesso',
    metadata: ['plano' => 'premium'],
);

$link = Transfeera::paymentLinks()->create($linkDTO);
// Retorna PaymentLinkResponseDTO { id, url (ex: https://pay.transfeera.com/abc123), status }
```

### Listar Links

```php
$links = Transfeera::paymentLinks()->list([
    'status' => 'active',         // active, expired, disabled
    'page' => 1,
    'per_page' => 20,
]);
```

### Consultar Link

```php
$link = Transfeera::paymentLinks()->get('pl_abc123');
```

### Deletar Link

```php
Transfeera::paymentLinks()->delete('pl_abc123');
```

---

## DTOs de Request

### ChargeDTO

```php
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;

$dto = new ChargeDTO(
    payerName: 'João Silva',
    value: 15000,                 // centavos
    payerDocument: '12345678909', // CPF
    dueDate: '2025-12-31',        // Y-m-d
    metadata: ['pedido_id' => 'PED123'],
);
```

### PaymentLinkDTO

```php
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;

$dto = new PaymentLinkDTO(
    name: 'Produto Premium',
    value: 29900,
    description: 'Assinatura Anual',
    expiresIn: 30,
    redirectUrl: 'https://meuapp.com/sucesso',
    metadata: ['plano' => 'premium'],
);
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `ReceivableException` | Erros na API de Recebimentos (outros que 401/422/429) |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/ChargeResourceTest.php
test('cria cobranca em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/charge' => Http::response([
            'id' => 'charge_1',
            'payer_name' => 'João Silva',
            'value' => 5000,
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::charges()->create('batch_123', [
        'payer_name' => 'João Silva',
        'value' => 5000,
        'due_date' => '2025-12-31',
        'document' => '12345678909',
        'document_type' => 'cpf',
    ]);

    expect($result->id)->toBe('charge_1');
});

test('download pdf cobranca', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges/chg_1/receivables/rec_1/pdf' => Http::response(
            '%PDF-1.4...', 200, ['Content-Type' => 'application/pdf']
        ),
    ]);

    $pdf = Transfeera::charges()->downloadPdf('chg_1', 'rec_1');
    expect($pdf)->toContain('%PDF');
});
```

---

## Webhooks de Recebimento

### Rotas

- `POST /webhooks/transfeera/receivables`

### Eventos Principais

| Evento | Descrição |
|--------|-----------|
| `pix.received` | Pix recebido (cash-in) |
| `pix.refunded` | Pix devolvido |
| `pix_key.created` | Chave criada |
| `pix_key.claimed` | Portabilidade solicitada |
| `pix_key.claimed_confirmed` | Portabilidade confirmada |
| `pix_key.claimed_canceled` | Portabilidade cancelada |
| `qr_code.created` | QR Code criado |
| `qr_code.revoked` | QR Code revogado |
| `charge.created` | Cobrança criada |
| `charge.completed` | Cobrança paga (boleto ou Pix) |
| `charge.expired` | Cobrança expirada |
| `charge.canceled` | Cobrança cancelada |
| `payment_link.created` | Link criado |
| `payment_link.completed` | Link pago |

### Listener Exemplo

```php
// App\Listeners\RecebimentoWebhookListener.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class RecebimentoWebhookListener
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'receivables') return;

        match ($event->type) {
            'pix.received' => $this->processarPixRecebido($event->payload),
            'charge.completed' => $this->confirmarPagamento($event->payload),
            'charge.expired' => $this->cobrancaExpirou($event->payload),
            default => null,
        };
    }

    private function processarPixRecebido(array $payload): void
    {
        $end2endId = $payload['data']['end2end_id'] ?? null;
        $valor = $payload['data']['value'] ?? 0;

        Log::info("Pix recebido: R$ " . number_format($valor / 100, 2, ',', '.') . " - E2E: {$end2endId}");

        // Associar a pedido, emitir NF, etc.
    }
}
```

---

## Exemplo Completo: E-commerce

```php
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\DTOs\Response\ChargeResponseDTO;

class RecebimentoService
{
    public function criarCobrancaPedido(array $pedido): ChargeResponseDTO
    {
        $chargeDTO = new ChargeDTO(
            payerName: $pedido['cliente_nome'],
            value: (int)($pedido['valor_total'] * 100),
            payerDocument: $pedido['cliente_cpf_cnpj'],
            dueDate: $pedido['vencimento'],
            metadata: [
                'pedido_id' => $pedido['id'],
                'itens' => $pedido['itens'],
            ],
        );

        return Transfeera::charges()->create($chargeDTO);
    }

    public function criarLinkCheckout(array $pedido): string
    {
        $link = Transfeera::paymentLinks()->create(new PaymentLinkDTO(
            name: "Pedido #{$pedido['id']}",
            value: (int)($pedido['valor_total'] * 100),
            description: "Pedido e-commerce #{$pedido['id']}",
            expiresIn: 7,
            redirectUrl: route('pedido.sucesso', $pedido['id']),
            metadata: ['pedido_id' => $pedido['id']],
        ));

        return $link->url;
    }

    public function gerarQrCodePdv(float $valor, string $pedidoId): array
    {
        $qr = Transfeera::pixQrCodes()->createImmediate(
            new \FlavioMoreir4\Transfeera\DTOs\PixQrCodeImmediateDTO(
                key: config('transfeera.pix_key_recebimento'),
                value: (int)($valor * 100),
                description: "Pedido #{$pedidoId}",
                additionalData: $pedidoId,
            )
        );

        return [
            'emv' => $qr->emv,
            'image_url' => $qr->imageUrl,
            'id' => $qr->id,
        ];
    }
}
```

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Webhook de devolução de Pix | 📋 Planejado | Evento `pix.refunded` não documentado |
| Batch create de cobranças | 📋 Planejado | API não suporta batch nativo |
| Split payment em cobranças | 📋 Planejado | Feature futura da Transfeera |

---

## Links Úteis

- [Referência API Recebimentos](https://docs.transfeera.dev/reference/endpoints.md)
- [Primeiro Recebimento](primeiro-recebimento.md) — Guia passo a passo
- [Webhooks](webhooks.md) — Configuração e segurança
- [Tratamento de Erros](erros.md) — Exceptions e retry