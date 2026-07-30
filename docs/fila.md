# Filas (Queues) — laravel-transfeera

Guia prático para processamento assíncrono de operações Transfeera usando filas do Laravel.

---

## Índice

1. [Job Base](#1-job-base-transfeerabasejob)
2. [Exemplo: Processar Pagamento em Lote](#2-exemplo-processar-pagamento-em-lote)
3. [Rate Limit e Backoff Inteligente](#3-rate-limit-e-backoff-inteligente)
4. [Monitoramento](#4-monitoramento)
5. [Boas Práticas](#5-boas-práticas)

---

## 1. Job Base: `TransfeeraBaseJob`

O SDK fornece uma classe abstrata `TransfeeraBaseJob` que já inclui:

- **Retry com backoff consciente de rate limit** — se a API retornar 429, o job usa o header `Retry-After` para aguardar o tempo correto
- **Log estruturado** — cada tentativa é logada com job class, domínio e número da tentativa
- **Máximo de 5 tentativas** com backoff progressivo

```php
use FlavioMoreir4\Transfeera\Jobs\TransfeeraBaseJob;

class MeuJob extends TransfeeraBaseJob
{
    public function handle(): void
    {
        $this->logInfo('Processando...');
        // Sua lógica aqui
    }
}
```

### Retry com Rate Limit

O `backoff()` é dinâmico: se o `RateLimitMonitor` detectar que o rate limit foi estourado, o atraso é calculado a partir do header `Retry-After` + margem de segurança. Caso contrário, segue backoff progressivo padrão:

| Tentativa | Backoff padrão | Backoff pós-rate-limit |
|-----------|----------------|----------------------|
| 1 | 5s | 0s |
| 2 | 15s | `Retry-After + 2s` |
| 3 | 45s | `(Retry-After + 2s) × 2` |
| 4 | 135s | `(Retry-After + 2s) × 4` |
| 5 | 405s | `(Retry-After + 2s) × 8` |

---

## 2. Exemplo: Processar Pagamento em Lote

```php
<?php

namespace App\Jobs;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Jobs\TransfeeraBaseJob;

class ProcessBatchPaymentJob extends TransfeeraBaseJob
{
    public function __construct(
        array $data,
        string $domain = 'payments',
    ) {
        parent::__construct($data, $domain);
    }

    public function handle(): void
    {
        $this->logInfo('Criando lote de pagamento...');

        $batch = Transfeera::batches()->create($this->data);

        $this->logInfo('Lote criado com sucesso.', [
            'batch_id' => $batch['id'] ?? 'unknown',
        ]);
    }
}
```

**Dispatch:**

```php
ProcessBatchPaymentJob::dispatch([
    'name' => 'Pagamentos fornecedores',
    'transfers' => [
        [
            'amount' => 15000, // R$ 150,00
            'bank_code' => '341',
            'agency' => '1234',
            'account' => '56789-0',
            'document' => '12345678909',
            'name' => 'João Silva',
        ],
    ],
]);
```

### Usando com Notifications

```php
ProcessBatchPaymentJob::dispatch($data)
    ->onQueue('transfeera')
    ->onConnection('redis')
    ->delay(now()->addMinutes(5));
```

---

## 3. Rate Limit e Backoff Inteligente

O SDK expõe o `RateLimitMonitor` para consultar o estado do rate limit em tempo real:

```php
use FlavioMoreir4\Transfeera\Services\RateLimitMonitor;

class BatchController
{
    public function __construct(
        private RateLimitMonitor $rateLimit,
    ) {}

    public function store()
    {
        if ($this->rateLimit->isThrottled('payments')) {
            // Redireciona para fila em vez de processar síncrono
            ProcessBatchPaymentJob::dispatch(request()->all());
            return response()->json(['status' => 'queued'], 202);
        }

        // Processa síncrono
        $result = Transfeera::batches()->create(request()->all());
        return response()->json($result);
    }
}
```

**Métodos disponíveis:**

| Método | Descrição |
|--------|-----------|
| `getRemaining(string $domain): ?int` | Requisições restantes na janela atual |
| `getLimit(string $domain): ?int` | Limite total da janela |
| `getReset(string $domain): ?int` | Timestamp de reset |
| `isThrottled(string $domain, float $threshold = 0.1): bool` | True se <= 10% restantes |
| `getState(string $domain): array` | Estado completo (remaining, limit, reset, updated_at) |

---

## 4. Monitoramento

### Logs Estruturados

Cada job loga automaticamente:

```log
[TransfeeraJob] Processando lote de pagamento...
  {"job":"App\\Jobs\\ProcessBatchPaymentJob","domain":"payments","attempt":1}

[TransfeeraJob] Lote criado com sucesso.
  {"job":"App\\Jobs\\ProcessBatchPaymentJob","domain":"payments","attempt":1}
```

### Falha Definitiva

Quando um job esgota todas as tentativas:

```log
[TransfeeraJob] Falha definitiva após todas as tentativas.
  {"job":"App\\Jobs\\ProcessBatchPaymentJob","domain":"payments","error":"...","attempts":5}
```

### Horizon / Pulse

Configure o Laravel Horizon ou Pulse para monitorar a fila `transfeera`:

```php
// config/horizon.php
'defaults' => [
    'supervisor-1' => [
        'queue' => ['transfeera', 'default'],
    ],
],
```

---

## 5. Boas Práticas

1. **Sempre use fila para operações em lote** — pagamentos em lote com muitos itens devem ser processados assíncronamente.
2. **Defina um queue específico** — use `->onQueue('transfeera')` para isolar o processamento.
3. **Monitore o rate limit** — use `RateLimitMonitor::isThrottled()` antes de decidir entre síncrono e fila.
4. **Capture exceções de validação** — `TransfeeraValidationException` deve ser tratada no job, não deixe chegar ao `failed()`.
5. **Teste com `SyncQueue`** — em testes, use `Queue::fake()->assertPushed()` para verificar o dispatch sem processar.
