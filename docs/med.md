# MED / Infrações — Laravel Transfeera

Este documento descreve os recursos da API de **MED (Mecanismo Especial de Devolução) / Infrações** implementados no SDK.

> Referência oficial: https://docs.transfeera.dev/reference/tag/MED

---

## Resource Disponível

| Resource | Classe | Métodos Principais |
|----------|--------|-------------------|
| **Infrações** | `InfractionResource` | `list()`, `get()`, `submitAnalysis()`, `submitBatchAnalysis()` |

---

## Infrações (InfractionResource)

O MED permite que o recebedor devolva valores de transações Pix recebidas indevidamente (ex: golpe, erro, fraude) ou conteste uma devolução indevida.

### Listar Infrações

```php
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$infractions = Transfeera::infractions()->list([
    'status' => 'pending_analysis', // pending_analysis, analysis_submitted, completed, contested
    'start_date' => '2025-01-01',
    'end_date' => '2025-12-31',
    'page' => 1,
    'per_page' => 20,
]);
// Retorna array<InfractionResponseDTO>
```

### Consultar Infração

```php
$infraction = Transfeera::infractions()->get('inf_abc123');
// Retorna InfractionResponseDTO
```

---

## Enviar Análise Individual

O recebedor (vítima) envia sua análise sobre a infração recebida.

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;

$analysisDTO = new InfractionAnalysisDTO(
    type: 'refund',              // 'refund' (devolver) | 'contest' (contestar)
    refundAmount: 50000,         // Obrigatório se type='refund' — valor em centavos
    description: 'Devolução por acordo entre as partes', // opcional
);

$result = Transfeera::infractions()->submitAnalysis('inf_abc123', $analysisDTO);
// Retorna InfractionAnalysisResponseDTO
```

### Tipos de Análise

| Tipo | Quando Usar | Campos Obrigatórios |
|------|-------------|---------------------|
| `refund` | Devolver o valor (reconhece que o Pix foi indevido) | `refundAmount` (em centavos) |
| `contest` | Contestar a infração (o Pix foi legítimo) | `description` (justificativa) |

---

## Enviar Análise em Lote

Para processar múltiplas infrações de uma vez:

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionBatchAnalysisDTO;
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;

$batchDTO = new InfractionBatchAnalysisDTO([
    new InfractionAnalysisDTO(
        infractionId: 'inf_001',
        type: 'refund',
        refundAmount: 30000, // R$ 300,00
        description: 'Devolução por golpe',
    ),
    new InfractionAnalysisDTO(
        infractionId: 'inf_002',
        type: 'contest',
        description: 'Pagamento legítimo - serviço prestado',
    ),
    // ... mais análises
]);

$result = Transfeera::infractions()->submitBatchAnalysis($batchDTO);
// Retorna InfractionBatchAnalysisResponseDTO
```

---

## DTOs

### InfractionAnalysisDTO (Request - Análise Individual)

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;

$dto = new InfractionAnalysisDTO(
    type: 'refund',           // 'refund' | 'contest'
    refundAmount: 50000,      // Obrigatório se type='refund' — centavos
    description: 'Acordo',    // Obrigatório se type='contest' | opcional se 'refund'
);
```

### InfractionBatchAnalysisDTO (Request - Análise em Lote)

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionBatchAnalysisDTO;
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;

$batchDTO = new InfractionBatchAnalysisDTO([
    new InfractionAnalysisDTO(infractionId: 'inf_001', type: 'refund', refundAmount: 30000),
    new InfractionAnalysisDTO(infractionId: 'inf_002', type: 'contest', description: 'Pagamento correto'),
    // ...
]);
```

### InfractionResponseDTO (Response)

```php
// Retornado por list(), get()
$infraction->id;              // ID da infração
$infraction->pixCashInId;     // ID do cash-in relacionado
$infraction->value;           // Valor da infração (centavos)
$infraction->payerName;       // Nome do pagador
$infraction->payerDocument;   // CPF/CNPJ do pagador
$infraction->receiverName;    // Nome do recebedor
$infraction->receiverPixKey;  // Chave Pix do recebedor
$infraction->status;          // pending_analysis, analysis_submitted, completed, contested
$infraction->createdAt;
$infraction->updatedAt;
```

---

## Exceptions Específicas

| Exception | Quando Lançada |
|-----------|----------------|
| `InfractionException` | Erros genéricos na API de MED/Infrações |
| `TransfeeraValidationException` | HTTP 422 — dados inválidos (use `$e->getErrors()`) |
| `TransfeeraAuthenticationException` | HTTP 401 — token/credenciais |
| `TransfeeraRateLimitException` | HTTP 429 — rate limit (use `$e->getRetryAfter()`) |

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Feature/InfractionResourceTest.php
test('lista infracoes', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions*' => Http::response([
            'data' => [
                ['id' => 'inf_1', 'value' => 50000, 'status' => 'pending_analysis'],
                ['id' => 'inf_2', 'value' => 20000, 'status' => 'completed'],
            ],
        ]),
    ]);

    $result = Transfeera::infractions()->list(['status' => 'pending_analysis']);
    expect($result)->toHaveCount(2);
    expect($result[0]->value)->toBe(50000);
});

test('consulta infracoes', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/inf_123' => Http::response([
            'id' => 'inf_123',
            'value' => 50000,
            'payer_name' => 'João Silva',
            'status' => 'pending_analysis',
        ]),
    ]);

    $result = Transfeera::infractions()->get('inf_123');
    expect($result->id)->toBe('inf_123');
    expect($result->status)->toBe('pending_analysis');
});

test('envia analise individual', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/inf_123/analysis' => Http::response([
            'infraction_id' => 'inf_123',
            'type' => 'refund',
            'refund_amount' => 30000,
            'status' => 'submitted',
        ], 201),
    ]);

    $result = Transfeera::infractions()->submitAnalysis('inf_123', [
        'type' => 'refund',
        'refund_amount' => 30000,
        'description' => 'Devolução por acordo',
    ]);

    expect($result->type)->toBe('refund');
    expect($result->refundAmount)->toBe(30000);
});

test('envia analise em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/batch-analysis' => Http::response([
            'submitted' => 2,
            'analyses' => [
                ['infraction_id' => 'inf_001', 'status' => 'submitted'],
                ['infraction_id' => 'inf_002', 'status' => 'submitted'],
            ],
        ], 201),
    ]);

    $result = Transfeera::infractions()->submitBatchAnalysis([
        ['infraction_id' => 'inf_001', 'type' => 'refund', 'refund_amount' => 30000],
        ['infraction_id' => 'inf_002', 'type' => 'contest', 'description' => 'Pagamento correto'],
    ]);

    expect($result->submitted)->toBe(2);
});
```

---

## Fluxo Completo: Devolução por MED

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\InfractionException;

class MedService
{
    public function processarDevolucao(string $infractionId, int $valorCentavos, string $motivo): void
    {
        try {
            $analysisDTO = new InfractionAnalysisDTO(
                type: 'refund',
                refundAmount: $valorCentavos,
                description: $motivo,
            );

            Transfeera::infractions()->submitAnalysis($infractionId, $analysisDTO);

            Log::info("Devolução MED processada", [
                'infraction_id' => $infractionId,
                'valor' => $valorCentavos / 100,
                'motivo' => $motivo,
            ]);

        } catch (InfractionException $e) {
            Log::error("Falha ao processar devolução MED", [
                'infraction_id' => $infractionId,
                'erro' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            throw $e;
        }
    }

    public function processarContestacao(string $infractionId, string $justificativa): void
    {
        try {
            $analysisDTO = new InfractionAnalysisDTO(
                type: 'contest',
                description: $justificativa,
            );

            Transfeera::infractions()->submitAnalysis($infractionId, $analysisDTO);

        } catch (InfractionException $e) {
            Log::error("Falha ao contestar infração", [
                'infraction_id' => $infractionId,
                'erro' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
```

---

## Webhooks de MED

### Rotas

- `POST /webhooks/transfeera/conta-certa` (o MED usa o mesmo endpoint webhook de Conta Certa)

### Eventos

| Evento | Descrição |
|--------|-----------|
| `med.infraction.received` | Nova infração recebida |
| `med.infraction.analysis_submitted` | Análise enviada pelo recebedor |
| `med.infraction.completed` | Processo finalizado (devolução confirmada ou contestação aceita) |

### Listener Exemplo

```php
// App\Listeners\MedWebhookListener.php
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

class MedWebhookListener
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        if ($event->domain !== 'med') return;

        match ($event->type) {
            'med.infraction.received' => $this->novaInfracao($event->payload),
            'med.infraction.analysis_submitted' => $this->analiseEnviada($event->payload),
            'med.infraction.completed' => $this->finalizado($event->payload),
            default => null,
        };
    }

    private function novaInfracao(array $payload): void
    {
        $infraction = $payload['data'] ?? [];
        Log::warning("Nova infração MED recebida", [
            'infraction_id' => $infraction['id'] ?? null,
            'valor' => ($infraction['value'] ?? 0) / 100,
            'pagador' => $infraction['payer_name'] ?? null,
        ]);
    }
}
```

---

## Configuração no `.env`

```env
# Opcional - secrets por domínio (MED usa conta_certa)
TRANSFEERA_WEBHOOK_SECRET_CONTA_CERTA=secret_contacerta_forte
```

---

## Roadmap (Documentado mas Não Implementado)

| Recurso | Status | Observação |
|---------|--------|------------|
| Consulta de status de análise | 📋 Planejado | Endpoint não documentado na API |
| Webhook de infração expirada | 📋 Planejado | Evento não documentado na API |
| Relatório de métricas MED | 📋 Planejado | Feature futura |

---

## Links Úteis

- [Referência API MED/Infrações](https://docs.transfeera.dev/reference/tag/MED)
- [Primeiro Pagamento](primeiro-pagamento.md) — Conceitos base
- [Webhooks](webhooks.md) — Configuração e segurança
- [Tratamento de Erros](erros.md) — Exceptions e retry