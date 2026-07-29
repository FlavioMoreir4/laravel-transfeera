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

```php
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;

try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraRateLimitException $e) {
    $retryAfter = $e->getRetryAfter(); // Segundos (header Retry-After)
    
    // Implementar backoff exponencial
    $waitTime = $retryAfter ?? 60;
    
    // Exemplo com Laravel Queue (retry automático)
    throw $e; // Laravel fará retry com backoff se configurado
}
```

**Configurar retry no Queue Worker:**

```php
// config/queue.php
'connections' => [
    'redis' => [
        'retry_after' => 90,
        'block_for' => 5,
    ],
],

// Job com backoff
class ProcessarLoteJob implements ShouldQueue
{
    public $tries = 3;
    public $backoff = [60, 120, 300]; // 1min, 2min, 5min
    
    public function handle() {
        Transfeera::batches()->create(...);
    }
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

## 8. Próximos Passos

- [Webhooks](webhooks.md) - Configuração e segurança
- [Primeiro Pagamento](primeiro-pagamento.md) - Lotes e transferências
- [Primeiro Recebimento](primeiro-recebimento.md) - Pix, QR Codes, cobranças
- [Documentação API](https://docs.transfeera.dev/reference/erros.md) - Referência completa de códigos