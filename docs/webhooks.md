# Guia: Configuração e Segurança de Webhooks

Este guia explica como configurar, validar e processar webhooks da Transfeera de forma segura.

## Visão Geral

A Transfeera envia eventos via HTTP POST para URLs configuradas. O SDK já expõe rotas prontas e valida a assinatura HMAC-SHA256 automaticamente.

## URLs da API

> Referência oficial: https://docs.transfeera.dev/reference/endpoints.md
> 
> URLs da API:
- Sandbox: https://api-sandbox.transfeera.com
- Produção (mTLS): https://api.mtls.transfeera.com
- Autenticação Sandbox: https://login-api-sandbox.transfeera.com/authorization
- Autenticação Produção: https://login-api.transfeera.com/authorization

O ServiceProvider registra automaticamente:

| Rota | Domínio | Header de Assinatura |
|------|---------|---------------------|
| `POST /webhooks/transfeera/payments` | Pagamentos | `X-Signature` |
| `POST /webhooks/transfeera/receivables` | Recebimentos | `X-Signature` |
| `POST /webhooks/transfeera/conta-certa` | Conta Certa | `X-Signature` |

>Signature` |

> As rotas são carregadas automaticamente via `TransfeeraServiceProvider::boot()`.

---

## 2. Configuração de Secrets

### No `.env`

```env
# Secret global (fallback)
TRANSFEERA_WEBHOOK_SECRET=secret_global_forte_aqui

# Ou por domínio (recomendado)
TRANSFEERA_WEBHOOK_SECRET_PAYMENTS=secret_pagamentos_forte
TRANSFEERA_WEBHOOK_SECRET_RECEIVABLES=secret_recebimentos_forte
TRANSFEERA_WEBHOOK_SECRET_CONTA_CERTA=secret_contacerta_forte
```

### No Painel Transfeera

1. Acesse **Configurações > Webhooks**
2. Adicione URLs:
   - `https://seuapp.com/webhooks/transfeera/payments`
   - `https://seuapp.com/webhooks/transfeera/receivables`
   - `https://seuapp.com/webhooks/transfeera/conta-certa`
3. Cole o **mesmo secret** usado no `.env`

---

## 3. Validação de Assinatura (Automática)

O `WebhookController` valida automaticamente:

```php
// Fluxo interno do controller
$payload = $request->getContent();           // Body bruto
$signature = $request->header('X-Signature'); // Header
$secret = config("transfeera.webhook_secrets.{$domain}", config('transfeera.webhook_secret'));

$validator = new SignatureValidator($secret);

$isValid = $domain === 'receivables'
    ? $validator->isValidForReceivables($payload, $signature)
    : $validator->isValid($payload, $signature);

if (! $isValid) {
    return response('Invalid signature', 401);
}
```

### Algoritmo HMAC-SHA256

| Domínio | Método |
|---------|--------|
| `payments` | `hash_hmac('sha256', $payload, $secret)` |
| `conta_certa` | `hash_hmac('sha256', $payload, $secret)` |
| `receivables` | `hash_hmac('sha256', $payload, $secret)` (mesmo, mas método dedicado) |

> **Importante**: Use `hash_equals()` para comparação timing-safe (já implementado no `SignatureValidator`).

---

## 4. Eventos Disponíveis

### Pagamentos (`payments`)

| Evento | Descrição | Payload Principal |
|--------|-----------|-------------------|
| `batch.created` | Lote criado | `batch` |
| `batch.processed` | Lote processado | `batch` |
| `batch.failed` | Falha no processamento | `batch`, `error` |
| `batch.canceled` | Lote cancelado | `batch` |
| `transfer.created` | Transferência criada | `transfer` |
| `transfer.completed` | Pagamento confirmado | `transfer` |
| `transfer.failed` | Pagamento falhou | `transfer`, `error` |
| `transfer.canceled` | Transferência cancelada | `transfer` |
| `billet.created` | Boleto criado | `billet` |
| `billet.paid` | Boleto pago | `billet` |

### Recebimentos (`receivables`)

| Evento | Descrição | Payload Principal |
|--------|-----------|-------------------|
| `pix.received` | Pix recebido | `cash_in` |
| `pix.refunded` | Pix devolvido | `cash_in`, `refund` |
| `pix_key.created` | Chave criada | `pix_key` |
| `pix_key.claimed` | Portabilidade solicitada | `claim` |
| `pix_key.claimed_confirmed` | Portabilidade confirmada | `claim` |
| `pix_key.claimed_canceled` | Portabilidade cancelada | `claim` |
| `qr_code.created` | QR Code criado | `qr_code` |
| `qr_code.revoked` | QR Code revogado | `qr_code` |
| `charge.created` | Cobrança criada | `charge` |
| `charge.completed` | Cobrança paga | `charge` |
| `charge.expired` | Cobrança expirada | `charge` |
| `charge.canceled` | Cobrança cancelada | `charge` |
| `payment_link.created` | Link criado | `payment_link` |

### Conta Certa (`conta_certa`)

| Evento | Descrição |
|--------|-----------|
| `validation.created` | Validação criada |
| `validation.completed` | Validação concluída |
| `validation.failed` | Validação falhou |

---

## 5. Criar Listeners Personalizados

### 1. Criar Listener

```bash
php artisan make:listener ProcessarPagamentoWebhook
```

### 2. Implementar

```php
<?php

namespace App\Listeners;

use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use Illuminate\Support\Facades\Log;

class ProcessarPagamentoWebhook
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        // Filtrar domínio
        if ($event->domain !== 'payments') return;

        match ($event->type) {
            'batch.processed' => $this->loteProcessado($event->payload),
            'transfer.completed' => $this->transferenciaConfirmada($event->payload),
            'transfer.failed' => $this->transferenciaFalhou($event->payload),
            'batch.failed' => $this->loteFalhou($event->payload),
            default => null,
        };
    }

    private function loteProcessado(array $payload): void
    {
        $batchId = $payload['data']['id'] ?? null;
        Log::info("Lote {$batchId} processado com sucesso");
        
        // Notificar equipe, atualizar status no ERP, etc.
    }

    private function transferenciaConfirmada(array $payload): void
    {
        $transfer = $payload['data'] ?? [];
        $transferId = $transfer['id'] ?? null;
        $amount = $transfer['amount'] ?? 0;
        
        Log::info("Transferência {$transferId} confirmada: R$ " . number_format($amount / 100, 2, ',', '.'));
        
        // Marcar fatura como paga, emitir nota, etc.
    }

    private function transferenciaFalhou(array $payload): void
    {
        $transfer = $payload['data'] ?? [];
        $error = $payload['error'] ?? 'Erro desconhecido';
        
        Log::error("Transferência falhou: {$error}", ['transfer' => $transfer]);
        
        // Notificar financeiro, retentativa, etc.
    }

    private function loteFalhou(array $payload): void
    {
        $batch = $payload['data'] ?? [];
        $error = $payload['error'] ?? 'Erro desconhecido';
        
        Log::error("Lote falhou: {$error}", ['batch' => $batch]);
    }
}
```

### 3. Registrar no EventServiceProvider

```php
// app/Providers/EventServiceProvider.php

protected $listen = [
    \FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived::class => [
        \App\Listeners\ProcessarPagamentoWebhook::class,
        \App\Listeners\ProcessarRecebimentoWebhook::class,
        \App\Listeners\ProcessarContaCertaWebhook::class,
    ],
];
```

> **Nota**: O SDK já registra `LogTransfeeraWebhook` automaticamente via `$listen` no ServiceProvider. Seus listeners são **adicionais**.

---

## 6. Validação Manual (Se Necessário)

### Em Controller Próprio

```php
use FlavioMoreir4\Transfeera\Webhooks\SignatureValidator;
use Illuminate\Http\Request;

public function webhookPagamentos(Request $request)
{
    $payload = $request->getContent();
    $signature = $request->header('X-Signature');
    $secret = config('transfeera.webhook_secrets.payments');

    $validator = new SignatureValidator($secret);
    
    if (! $validator->isValid($payload, $signature)) {
        return response('Invalid signature', 401);
    }

    // Processar...
    $event = json_decode($payload, true);
    // ...
}
```

### Testando Localmente (ngrok)

```bash
# Terminal 1: ngrok
ngrok http 8000

# Terminal 2: configurar no painel Transfeera
# URL: https://abc123.ngrok.io/webhooks/transfeera/payments
```

---

## 7. Reenvio de Eventos (Retry)

### Via SDK

```php
// Reenviar evento específico falho
Transfeera::paymentsWebhooks()->resendEvent($eventId);

// Ou para recebimentos
Transfeera::receivablesWebhooks()->resendEvent($eventId);

// Conta Certa
Transfeera::contaCertaWebhooks()->resendEvent($eventId);
```

### Listar Eventos Falhos

```php
$failedEvents = Transfeera::paymentsWebhooks()->listEvents([
    'status' => 'failed',
    'page' => 1,
    'per_page' => 50
]);
```

---

## 8. Boas Práticas de Segurança

| Prática | Descrição |
|---------|-----------|
| **Secrets fortes** | Use `openssl rand -hex 32` para gerar |
| **Secrets por domínio** | Isola vazamento (payments ≠ receivables) |
| **HTTPS obrigatório** | Webhooks só funcionam em HTTPS |
| **Validação timing-safe** | `hash_equals()` já usado no SDK |
| **Idempotência** | Webhooks podem ser reenviados - guarde `event_id` processados |
| **Logs de auditoria** | Logue todos eventos recebidos (sucesso/falha) |
| **Rate limiting** | Configure no nginx/load balancer |

---

## 9. Testes de Webhook

### Payload de Teste (Pagamentos)

```json
{
  "event": "transfer.completed",
  "data": {
    "id": "transf_abc123",
    "batch_id": "batch_456",
    "amount": 15000,
    "pix_key": "fornecedor@email.com",
    "pix_key_type": "email",
    "status": "completed",
    "completed_at": "2025-01-15T10:30:00Z"
  },
  "timestamp": "2025-01-15T10:30:01Z"
}
```

### Enviar Teste via curl

```bash
PAYLOAD='{"event":"transfer.completed","data":{"id":"test_123","amount":1000}}'
SECRET=seu_secret_aqui
SIGNATURE=$(echo -n "$PAYLOAD" | openssl dgst -sha256 -hmac "$SECRET" | awk '{print $2}')

curl -X POST https://seuapp.com/webhooks/transfeera/payments \
  -H "Content-Type: application/json" \
  -H "X-Signature: $SIGNATURE" \
  -d "$PAYLOAD"
```

---

## 10. Próximos Passos

- [Primeiro Pagamento](primeiro-pagamento.md) - Transferências e lotes
- [Primeiro Recebimento](primeiro-recebimento.md) - Chaves Pix, QR Codes, cobranças
- [Tratamento de Erros](erros.md) - Códigos, retry, exceções
- [Documentação API](https://docs.transfeera.dev/reference/eventos.md) - Referência completa de eventos