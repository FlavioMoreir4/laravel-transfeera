# Guia: Tratamento de Erros e Exceções

Este guia explica como lidar com erros da API Transfeera usando as exceções tipadas do SDK.

## Hierarquia de Exceções

```
TransfeeraException (base)
├── TransfeeraAuthenticationException (401)
├── TransfeeraValidationException (422)
├── TransfeeraRateLimitException (429)
├── TransfeeraNotFoundException (404)
├── TransfeeraConflictException (409)
├── TransfeeraMismatchException (400)
├── TransfeeraServerException (5xx)
└── TransfeeraMtlException (mTLS)
```

---

## 1. Capturando Erros Básicos

```php
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\{
    TransfeeraException,
    TransfeeraValidationException,
    TransfeeraAuthenticationException,
    TransfeeraRateLimitException
};

try {
    $batch = Transfeera::batches()->create([
        'name' => 'Lote Teste'
    ]);
} catch (TransfeeraValidationException $e) {
    // Erro 422 - Dados inválidos
    echo "Campos inválidos: ";
    print_r($e->getErrors());
    
    // Exemplo de $e->getErrors():
    // ['name' => ['O nome é obrigatório']]
    
} catch (TransfeeraAuthenticationException $e) {
    // Erro 401 - Credenciais inválidas ou token expirado
    echo "Falha na autenticação: {$e->getMessage()}";
    // Verificar CLIENT_ID, CLIENT_SECRET, token expirado
    
} catch (TransfeeraRateLimitException $e) {
    // Erro 429 - Muitas requisições
    $retryAfter = $e->getRetryAfter(); // Segundos para aguardar
    echo "Rate limit atingido. Aguardar {$retryAfter}s";
    
} catch (TransfeeraException $e) {
    // Outros erros (404, 409, 500, etc.)
    echo "Erro: {$e->getMessage()} (Código: {$e->getStatusCode()})";
}
```

---

## 2. Exceções Detalhadas

### TransfeeraValidationException (422)

Lançada quando a API retorna 422 Unprocessable Entity.

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;

try {
    $charge = Transfeera::charges()->create(new ChargeDTO(
        payerName: '',
        value: -100,
        dueDate: 'invalid-date'
    ));
} catch (TransfeeraValidationException $e) {
    // $e->getErrors() retorna array associativo
    foreach ($e->getErrors() as $field => $messages) {
        echo "Campo '{$field}': " . implode(', ', $messages) . "\n";
    }
    
    // Exemplo de saída:
    // Campo 'payer_name': O nome do pagador é obrigatório
    // Campo 'value': O valor deve ser maior que zero
    // Campo 'due_date': Formato de data inválido (use Y-m-d)
}
```

**Métodos úteis:**
- `$e->getErrors()` - Array associativo campo => mensagens
- `$e->getFirstError()` - Primeira mensagem de erro
- `$e->hasError('field')` - Verifica se campo tem erro

### TransfeeraAuthenticationException (401)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;

try {
    Transfeera::batches()->list();
} catch (TransfeeraAuthenticationException $e) {
    // Causas comuns:
    // - CLIENT_ID/SECRET inválidos
    // - Token expirado (SDK tenta renovar automaticamente)
    // - Scope insuficiente (ex.: precisa account_id)
    
    Log::error('Auth falhou', [
        'message' => $e->getMessage(),
        'status' => $e->getStatusCode()
    ]);
    
    // Em produção: alertar equipe, forçar renovação de token
}
```

### TransfeeraRateLimitException (429)

Lançada quando a API retorna HTTP 429 (Too Many Requests). A classe expõe os headers de rate limit da API Transfeera, permitindo implementar backoff inteligente e monitoramento.

#### Métodos Disponíveis

| Método | Header de Origem | Descrição |
|--------|------------------|-----------|
| `getRetryAfter(): ?int` | `Retry-After` | Segundos recomendados para aguardar antes de tentar novamente |
| `getLimit(): ?int` | `X-RateLimit-Limit` | Limite máximo de requisições permitidas na janela atual |
| `getRemaining(): ?int` | `X-RateLimit-Remaining` | Requisições restantes na janela atual |
| `getReset(): ?int` | `X-RateLimit-Reset` | Timestamp Unix de quando o rate limit será resetado |

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;

try {
    $batch = Transfeera::batches()->create([...]);
} catch (TransfeeraRateLimitException $e) {
    $retryAfter = $e->getRetryAfter(); // Segundos (header Retry-After)
    $limit     = $e->getLimit();       // Limite total da janela
    $remaining = $e->getRemaining();   // Requisições que ainda pode fazer
    $reset     = $e->getReset();       // Timestamp Unix do próximo reset
    
    Log::warning('Rate limit atingido', [
        'retry_after' => $retryAfter,
        'limit'       => $limit,
        'remaining'   => $remaining,
        'reset_at'    => $reset ? date('Y-m-d H:i:s', $reset) : null,
    ]);
    
    // Aguardar o tempo recomendado pela API
    $waitTime = $retryAfter ?? 60;
    sleep($waitTime);
    
    // Tentar novamente
    $batch = Transfeera::batches()->create([...]);
}
```

**Monitoramento preventivo:** Use `$e->getRemaining()` antes de atingir o limite para disparar alertas quando o consumo estiver alto:

```php
if ($remaining !== null && $remaining < 10) {
    Log::warning('Rate limit próximo do limite', [
        'remaining' => $remaining,
        'limit'     => $limit,
    ]);
}
```

### TransfeeraNotFoundException (404)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraNotFoundException;

try {
    $batch = Transfeera::batches()->get('batch_inexistente');
} catch (TransfeeraNotFoundException $e) {
    // Recurso não encontrado
    // $e->getResourceType() // 'batch', 'transfer', 'charge', etc.
    // $e->getResourceId()
    
    echo "Recurso não encontrado: {$e->getResourceType()} {$e->getResourceId()}";
}
```

### TransfeeraConflictException (409)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraConflictException;

try {
    Transfeera::pixKeys()->create(new PixKeyDTO(
        type: 'cpf',
        value: '12345678909'  // Já existe
    ));
} catch (TransfeeraConflictException $e) {
    // Conflito: chave Pix já cadastrada, lote já processado, etc.
    echo "Conflito: {$e->getMessage()}";
}
```

### TransfeeraServerException (5xx)

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraServerException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraServerException $e) {
    // Erro 500, 502, 503, 504 da Transfeera
    // Implementar retry com backoff
    
    Log::critical('Transfeera indisponível', [
        'status' => $e->getStatusCode(),
        'message' => $e->getMessage()
    ]);
    
    // Retry com backoff exponencial recomendado
}
```

---

## 3. Middleware Global de Tratamento (Laravel)

### Handler Global

```php
// app/Exceptions/Handler.php

use FlavioMoreir4\Transfeera\Exceptions\{
    TransfeeraException,
    TransfeeraValidationException,
    TransfeeraAuthenticationException,
    TransfeeraRateLimitException
};

public function render($request, Throwable $e): Response
{
    // API JSON
    if ($request->expectsJson()) {
        return match (true) {
            $e instanceof TransfeeraValidationException => response()->json([
                'error' => 'validation_error',
                'message' => 'Dados inválidos',
                'errors' => $e->getErrors()
            ], 422),
            
            $e instanceof TransfeeraAuthenticationException => response()->json([
                'error' => 'unauthorized',
                'message' => 'Credenciais inválidas ou token expirado'
            ], 401),
            
            $e instanceof TransfeeraRateLimitException => response()->json([
                'error' => 'rate_limit',
                'message' => 'Muitas requisições. Tente novamente em ' . ($e->getRetryAfter() ?? 60) . ' segundos.',
                'retry_after' => $e->getRetryAfter()
            ], 429)->header('Retry-After', $e->getRetryAfter() ?? 60),
            
            $e instanceof TransfeeraException => response()->json([
                'error' => 'transfeera_error',
                'message' => $e->getMessage(),
                'status_code' => $e->getStatusCode()
            ], $e->getStatusCode() ?: 500),
            
            default => parent::render($request, $e),
        };
    }
    
    return parent::render($request, $e);
}
```

---

## 4. Retry Automático com Laravel

### Job com Backoff

```php
<?php

namespace App\Jobs;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CriarLotePagamento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [60, 120, 300]; // 1min, 2min, 5min
    public $timeout = 60;

    public function __construct(
        public array $dadosLote,
        public array $transferencias
    ) {}

    public function handle(): void
    {
        // 1. Criar lote
        $batch = Transfeera::batches()->create([
            'name' => $this->dadosLote['name'],
            'type' => $this->dadosLote['type'] ?? 'immediate',
        ]);

        // 2. Adicionar transferências
        foreach ($this->transferencias as $t) {
            Transfeera::transfers()->create($batch['id'], [
                'amount' => $t['amount'],
                'pix_key' => $t['pix_key'],
                'pix_key_type' => $t['pix_key_type'],
                'description' => $t['description'] ?? null,
            ]);
        }

        // 3. Processar
        Transfeera::batches()->process($batch['id']);
    }

    // Retry automático em exceções específicas
    public function failed(Throwable $exception): void
    {
        Log::error('Falha ao criar lote', [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'dados' => $this->dadosLote
        ]);
        
        // Notificar equipe (Slack, email, etc.)
    }
}
```

### Dispatch com Delay (Agendamento)

```php
// Processar lote em horário específico
CriarLotePagamento::dispatch($dadosLote, $transferencias)
    ->delay(now()->addHours(2)); // Processar às 02:00

// Ou usar scheduler
$schedule->job(new CriarLotePagamento($dados, $transfs))
    ->dailyAt('02:00');
```

---

## 5. Códigos de Erro Comuns da API

| Código HTTP | Exceção | Causa Comum |
|-------------|---------|-------------|
| 400 | TransfeeraMismatchException | Payload malformado, JSON inválido |
| 401 | TransfeeraAuthenticationException | Token expirado, credenciais erradas |
| 403 | TransfeeraException | Sem permissão (scope, IP bloqueado) |
| 404 | TransfeeraNotFoundException | Recurso não existe |
| 409 | TransfeeraConflictException | Chave duplicada, lote já processado |
| 422 | TransfeeraValidationException | Validação falhou (ver `$e->getErrors()`) |
| 429 | TransfeeraRateLimitException | Rate limit - ver `Retry-After` |
| 500/502/503/504 | TransfeeraServerException | Erro interno Transfeera - retry |

---

## 6. Debug e Logs

### Log Estruturado

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraException $e) {
    Log::error('Erro Transfeera', [
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'status_code' => $e->getStatusCode(),
        'request_id' => $e->getRequestId() ?? null, // Se disponível
        'context' => [
            'endpoint' => $e->getEndpoint() ?? null,
            'payload' => $e->getPayload() ?? null,
        ],
        'trace' => $e->getTraceAsString(),
    ]);
    
    throw $e; // Re-lançar para handler global
}
```

### Habilitar Debug HTTP

```php
// config/transfeera.php
'debug' => env('TRANSFEERA_DEBUG', false),

// No código (temporário)
$client = TransfeeraClient::withDebug(true);
```

---

## 7. Checklist de Produção

| Item | Status |
|------|--------|
| ✅ Handler global configurado | |
| ✅ Secrets de webhook configurados | |
| ✅ Rate limit com retry automático | |
| ✅ Logs estruturados para errors | |
| ✅ Alertas para 5xx e rate limits | |
| ✅ Testes de erro no sandbox | |
| ✅ Documentação de runbook para equipe | |

---

## 8. Estratégia de Retry para Rate Limit

Esta seção descreve como implementar uma estratégia robusta de retry ao lidar com `TransfeeraRateLimitException` em produção — tanto em chamadas síncronas quanto em jobs de fila.

### Retry com Backoff Exponencial (Síncrono)

Para operações síncronas (CLI, comandos artesanais, scripts), implemente retry com backoff exponencial respeitando o `Retry-After` da API:

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraServerException;

function retryWithBackoff(callable $operation, int $maxRetries = 3): mixed
{
    $attempt = 0;

    while (true) {
        try {
            return $operation();
        } catch (TransfeeraRateLimitException $e) {
            $attempt++;

            if ($attempt > $maxRetries) {
                // Esgotou as tentativas — relançar para o handler global
                throw $e;
            }

            // Usar Retry-After da API, com backoff progressivo como fallback
            $waitTime = $e->getRetryAfter()
                ?? min(60 * (2 ** ($attempt - 1)), 300); // 60s, 120s, 240s...

            Log::warning("Rate limit na tentativa {$attempt}/{$maxRetries}", [
                'wait_time' => $waitTime,
                'remaining' => $e->getRemaining(),
                'limit'     => $e->getLimit(),
                'reset_at'  => $e->getReset()
                    ? date('Y-m-d H:i:s', $e->getReset())
                    : null,
            ]);

            sleep($waitTime);

        } catch (TransfeeraServerException $e) {
            $attempt++;

            if ($attempt > $maxRetries) {
                throw $e;
            }

            // Backoff mais agressivo para erros de servidor (5xx)
            $waitTime = min(5 * (2 ** ($attempt - 1)), 120);

            Log::warning("Erro de servidor na tentativa {$attempt}/{$maxRetries}", [
                'status'    => $e->getStatusCode(),
                'wait_time' => $waitTime,
            ]);

            sleep($waitTime);
        }
    }
}

// Uso
$batch = retryWithBackoff(fn () => Transfeera::batches()->create([
    'name' => 'Lote Teste',
]));
```

### Retry com Laravel Queue (Backoff Dinâmico)

Para jobs em fila, configure o backoff baseado no `Retry-After` retornado pela API:

```php
<?php

namespace App\Jobs;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessarLotePagamento implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;
    public $maxExceptions = 3;
    public $timeout = 120;

    public function __construct(
        public array $dadosLote,
        public array $transferencias
    ) {}

    public function handle(): void
    {
        $batch = Transfeera::batches()->create($this->dadosLote);

        foreach ($this->transferencias as $t) {
            Transfeera::transfers()->create($batch['id'], $t);
        }

        Transfeera::batches()->process($batch['id']);
    }

    /**
     * Backoff fixo progressivo (em segundos).
     * O Laravel consulta este método a cada retry automático.
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300];
    }

    /**
     * Tempo máximo de retry.
     */
    public function retryUntil(): DateTime
    {
        return now()->addMinutes(30);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Falha permanente ao processar lote', [
            'exception' => get_class($exception),
            'message'   => $exception->getMessage(),
            'dados'     => $this->dadosLote,
        ]);
    }
}
```

### Middleware HTTP com Retry (Laravel Http Client)

Para chamadas HTTP diretas que não passam pelo SDK, utilize o método `retry()` do cliente HTTP do Laravel:

```php
use Illuminate\Support\Facades\Http;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;

$response = Http::retry(3, function (int $attempt, Exception $e) {
    if ($e instanceof TransfeeraRateLimitException) {
        return $e->getRetryAfter() ?? 60; // Delay baseado na resposta
    }
    return 100; // Delay padrão (ms)
}, function (Exception $e) {
    // Só retentar em rate limit ou erro de servidor
    return $e instanceof TransfeeraRateLimitException
        || $e instanceof TransfeeraServerException;
})->post('https://api.transfeera.com/...', [...]);
```

### Dicas para Produção

1. **Sempre respeitar `Retry-After`** — A API Transfeera informa o tempo exato de espera via `$e->getRetryAfter()`. Prefira este valor ao invés de um backoff fixo.
2. **Monitorar `$e->getRemaining()`** — Use este valor para alertar antes de atingir o limite. Crie alertas quando `remaining < 10% do limit`.
3. **Log estruturado** — Em todo rate limit, registre `retry_after`, `limit`, `remaining` e `reset` para debugging e dimensionamento de capacidade.
4. **Usar filas para operações críticas** — Jobs do Laravel com `backoff` e `retryUntil` são mais resilientes que chamadas síncronas.
5. **Timeout adequado** — Configure `$timeout` no job maior que o maior `Retry-After` esperado (ex.: 120s).
6. **Evitar retry infinito** — Sempre defina `$tries` máximo e um `retryUntil` para evitar jobs presos na fila.

---

## 9. Próximos Passos

- [Webhooks](webhooks.md) - Configuração e segurança
- [Primeiro Pagamento](primeiro-pagamento.md) - Lotes e transferências
- [Primeiro Recebimento](primeiro-recebimento.md) - Pix, QR Codes, cobranças
- [Documentação API](https://docs.transfeera.dev/reference/erros.md) - Referência completa de códigos