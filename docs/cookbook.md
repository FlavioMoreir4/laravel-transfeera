# Cookbook — laravel-transfeera

Receitas práticas para cenários reais com a API Transfeera.

---

## Índice

1. [Pagamento em Lote](#1-pagamento-em-lote)
2. [Cobrança com Boleto + Pix](#2-cobrança-com-boleto--pix)
3. [Consulta e Devolução de Pix Recebido](#3-consulta-e-devolução-de-pix-recebido)
4. [Portabilidade de Chave Pix](#4-portabilidade-de-chave-pix)
5. [Pix Automático — Assinatura Recorrente](#5-pix-automático--assinatura-recorrente)
6. [Validação de Conta Bancária (Conta Certa)](#6-validação-de-conta-bancária-conta-certa)
7. [Recebimento e Validação de Webhook](#7-recebimento-e-validação-de-webhook)
8. [Infração MED — Análise e Devolução](#8-infração-med--análise-e-devolução)
9. [Hub de Contas — Multi-Tenancy](#9-hub-de-contas--multi-tenancy)
10. [Observabilidade — Métricas e Tracing](#10-observabilidade--métricas-e-tracing)

---

## 1. Pagamento em Lote

Cria um lote com múltiplas transferências e processa.

```php
use FlavioMoreir4\Transfeera\Facades\Transfeera;

// 1. Criar o lote
$batch = Transfeera::batches()->create([
    'name' => 'Pagamento Fornecedores Julho',
    'scheduling_date' => '2025-08-05',
]);

// 2. Adicionar transferências ao lote
$transfer1 = Transfeera::transfers()->create($batch->id, [
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789-0',
    'account_type' => 'checking',
    'document' => '12345678909',
    'name' => 'João Silva',
    'amount' => 150000, // R$ 1.500,00
    'description' => 'Fatura 001',
]);

$transfer2 = Transfeera::transfers()->create($batch->id, [
    'bank_code' => '237',
    'agency' => '4321',
    'account' => '98765-0',
    'account_type' => 'savings',
    'document' => '98765432100',
    'name' => 'Maria Souza',
    'amount' => 89000, // R$ 890,00
    'description' => 'Fatura 002',
]);

// 3. Processar o lote (fecha e envia para execução)
$processed = Transfeera::batches()->process($batch->id);

// 4. Acompanhar status
$status = Transfeera::batches()->get($batch->id);
echo $status->status; // 'processing', 'completed', 'failed'
```

## 2. Cobrança com Boleto + Pix

Cria uma cobrança que pode ser paga via boleto ou Pix.

```php
// Criar cobrança
$charge = Transfeera::charges()->create([
    'payer_name' => 'Empresa XYZ Ltda',
    'payer_document' => '11222333444455',
    'value' => 50000, // R$ 500,00 em centavos
    'due_date' => '2025-09-15',
    'description' => 'Serviço de consultoria - Setembro',
    'fine_rate' => 2.0,
    'interest_rate' => 0.033,
]);

// URL do boleto
echo $charge->boletoUrl;

// Pix copia-e-cola
echo $charge->pixQrCode;

// Download do comprovante em PDF
$pdf = Transfeera::charges()->downloadPdf($charge->id, $charge->id);

// Cancelar cobrança se necessário
Transfeera::charges()->cancel($charge->id);

// Listar cobranças com filtros
$cobrancas = Transfeera::charges()->list([
    'status' => 'pending',
    'start_date' => '2025-08-01',
    'end_date' => '2025-08-31',
    'page' => 1,
    'per_page' => 50,
]);
```

## 3. Consulta e Devolução de Pix Recebido

```php
// Listar Pix recebidos no período
$pixRecebidos = Transfeera::pixCashIn()->list([
    'start_date' => '2025-08-01',
    'end_date' => '2025-08-31',
]);

// Consultar um Pix específico pelo end2endId
$pix = Transfeera::pixCashIn()->getByEnd2EndId('E2E12345678901234567890');

// Solicitar devolução parcial
$refund = Transfeera::pixCashIn()->requestRefund(
    end2EndId: 'E2E12345678901234567890',
    data: [
        'amount' => 5000, // R$ 50,00 em centavos
        'description' => 'Devolução por acordo',
    ],
);

// Consultar histórico de devoluções
$devolucoes = Transfeera::pixCashIn()->getRefunds('E2E12345678901234567890');
```

## 4. Portabilidade de Chave Pix

```php
// 1. Listar chaves existentes
$keys = Transfeera::pixKeys()->list();

// 2. Reivindicar portabilidade de uma chave
$claim = Transfeera::pixKeys()->claim('11999999999');

// 3. Confirmar portabilidade com código recebido no DICT
$confirmed = Transfeera::pixKeys()->confirmClaim('claim_abc123');

// 4. Ou cancelar a solicitação
Transfeera::pixKeys()->cancelClaim('claim_abc123');

// 5. Criar chave própria
$newKey = Transfeera::pixKeys()->create([
    'type' => 'email',
    'value' => 'cobranca@empresa.com',
]);

// 6. Reenviar código de verificação
Transfeera::pixKeys()->resendVerificationCode($newKey->id);

// 7. Verificar chave com código recebido
Transfeera::pixKeys()->verify($newKey->id, '123456');

// Consultar chave Pix no DICT (pagamentos)
$dictInfo = Transfeera::pix()->lookupKey('11999999999');
echo "Titular: {$dictInfo->payerName}";
```

## 5. Pix Automático — Assinatura Recorrente

```php
// 1. Criar autorização de cobrança recorrente
$auth = Transfeera::pixAutomaticoAuthorizations()->create([
    'payer_document' => '12345678909',
    'payer_name' => 'João Silva',
    'max_value' => 99900, // R$ 999,00
    'expiration_date' => '2026-12-31',
    'split_payment' => [
        'percentage' => 70,
        'target_document' => '98765432100',
    ],
]);

// 2. Criar instrução de pagamento (cobrança do mês)
$payment = Transfeera::pixAutomaticoPaymentIntents()->create(
    authorizationId: $auth->id,
    data: [
        'value' => 7990, // R$ 79,90
        'description' => 'Assinatura Premium - Agosto',
        'due_date' => '2025-08-15',
    ],
);

// 3. Se falhar, reenviar retentativa
Transfeera::pixAutomaticoPaymentIntents()->resendRetry($payment->id);

// 4. Cancelar instrução se necessário
Transfeera::pixAutomaticoPaymentIntents()->cancel($payment->id);

// 5. Listar instruções de pagamento
$payments = Transfeera::pixAutomaticoPaymentIntents()->list([
    'authorization_id' => $auth->id,
]);

// 6. Cancelar autorização inteira
Transfeera::pixAutomaticoAuthorizations()->cancel($auth->id);
```

## 6. Validação de Conta Bancária (Conta Certa)

```php
// Validar dados bancários antes de pagar
$validation = Transfeera::contaCertaValidations()->create([
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789-0',
    'account_type' => 'checking',
    'document' => '12345678909',
    'name' => 'João Silva',
]);

// Aguardar resultado (campo 'status' no DTO)
$result = Transfeera::contaCertaValidations()->get($validation->id);

if ($result->status === 'approved') {
    echo "Conta validada com sucesso!";
} elseif ($result->status === 'rejected') {
    echo "Conta rejeitada: {$result->reason}";
}

// Listar bancos suportados
$banks = Transfeera::contaCertaBanks()->list();

// Listar validações recentes
$validacoes = Transfeera::contaCertaValidations()->list([
    'status' => 'pending',
    'page' => 1,
    'per_page' => 20,
]);
```

## 7. Recebimento e Validação de Webhook

```php
// No arquivo de rotas (ex.: routes/transfeera-webhooks.php)
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Route;

Route::post('transfeera/webhook/payments', function (Request $request) {
    $payload = $request->getContent();
    $signature = $request->header('X-Webhook-Signature');
    $secret = config('transfeera.webhook_secret_payments');

    if (! Transfeera::paymentsWebhooks()->verifySignature($payload, $signature, $secret)) {
        abort(401, 'Assinatura inválida');
    }

    // Processar evento
    $event = $request->json();
    Log::info('Webhook recebido', ['type' => $event['type']]);

    return response()->json(['status' => 'ok']);
});

// Reenviar evento se necessário
Transfeera::paymentsWebhooks()->resendEvent('evt_abc123');

// Listar eventos com filtros
$eventos = Transfeera::paymentsWebhooks()->listEvents([
    'status' => 'failed',
    'start_date' => '2025-08-01',
]);
```

## 8. Infração MED — Análise e Devolução

```php
// 1. Listar infrações recebidas
$infracoes = Transfeera::infractions()->list([
    'status' => 'open',
    'page' => 1,
]);

// 2. Consultar detalhes de uma infração
$infracao = Transfeera::infractions()->get('inf_123');
echo "Valor original: {$infracao->amount}"; // em centavos

// 3. Enviar análise individual (devolução ou contestação)
Transfeera::infractions()->submitAnalysis('inf_123', [
    'type' => 'refund',
    'refund_amount' => 5000, // R$ 50,00
    'description' => 'Devolução integral conforme acordo',
]);

// 4. Ou enviar análises em lote
Transfeera::infractions()->submitBatchAnalysis([
    [
        'infraction_id' => 'inf_001',
        'type' => 'refund',
        'refund_amount' => 3000,
    ],
    [
        'infraction_id' => 'inf_002',
        'type' => 'contest',
        'description' => 'Pagamento correto — serviço prestado',
    ],
]);
```

## 9. Hub de Contas — Multi-Tenancy

```php
// Criar uma conta digital para um cliente
$account = Transfeera::accounts()->create([
    'name' => 'Empresa XYZ Ltda',
    'document' => '11222333444455',
    'email' => 'financeiro@xyz.com',
]);

// Operar em nome da conta (passar accountId)
$batches = Transfeera::batches(accountId: $account->id)->list();

// Ou criar resource específico com accountId
$charges = Transfeera::charges(accountId: $account->id);
$charge = $charges->create([
    'payer_name' => 'Cliente da XYZ',
    'value' => 5000,
]);

// Listar contas
$contas = Transfeera::accounts()->list();

// Encerrar conta (remove chaves Pix vinculadas)
Transfeera::accounts()->close($account->id);
```

## 10. Observabilidade — Métricas e Tracing

### Evento de requisição completa

O SDK dispara `TransfeeraRequestComplete` após cada chamada à API.
Use listeners para enviar métricas ao seu sistema de observabilidade.

```php
// No AppServiceProvider::boot()
use FlavioMoreir4\Transfeera\Events\TransfeeraRequestComplete;
use Illuminate\Support\Facades\Event;

Event::listen(TransfeeraRequestComplete::class, function ($event) {
    // Log detalhado
    Log::channel('transfeera')->info('API Request', [
        'domain' => $event->domain,
        'method' => $event->method,
        'url' => $event->url,
        'status' => $event->status,
        'duration_ms' => round($event->duration * 1000, 2),
    ]);
});
```

### Middleware de logging avançado

O `LoggingMiddleware` agora suporta sanitização, truncamento e níveis por domínio:

```php
// config/transfeera.php
'logging' => [
    'enabled' => env('TRANSFEERA_LOGGING_ENABLED', true),
    'channel' => env('TRANSFEERA_LOGGING_CHANNEL', 'stack'),
    'level' => env('TRANSFEERA_LOGGING_LEVEL', 'info'),
    'log_headers' => env('TRANSFEERA_LOGGING_LOG_HEADERS', false),
    'log_response_body' => env('TRANSFEERA_LOGGING_LOG_RESPONSE_BODY', false),
    'sanitize' => env('TRANSFEERA_LOGGING_SANITIZE', true),
    'max_body_length' => env('TRANSFEERA_LOGGING_MAX_BODY_LENGTH', 4096),
    'level_by_domain' => [
        'payments' => 'debug',     // pagamentos em debug
        'conta_certa' => 'info',    // validações em info
    ],
    'level_by_status' => [
        500 => 'error',             // 5xx sempre como error
        429 => 'warning',           // rate limit como warning
        200 => 'info',              // sucesso como info
    ],
],
```

**Sanitização**: campos como `client_secret`, `token`, `document`, `account`, `agency` são automaticamente mascarados:

```log
# Antes (inseguro):
"request_data": {"document": "12345678909", "account": "56789-0"}

# Depois (sanitizado):
"request_data": {"document": "12*****09", "account": "56***0"}
```

**Truncamento**: payloads acima de `max_body_length` (4096 chars por padrão) são resumidos:

```log
"request_data": {"_truncated": true, "_original_size": 15000, "_preview": "{\"data\":[{\"name\":...}"}
```

### Middleware de métricas

```php
// config/transfeera.php
'metrics' => [
    'enabled' => env('TRANSFEERA_METRICS_ENABLED', false),
    'prefix' => 'transfeera', // prefixo dos nomes das métricas
],
```

### OpenTelemetry — Tracing Distribuído

Para rastrear requisições ponta a ponta com OpenTelemetry:

```bash
composer require open-telemetry/opentelemetry open-telemetry/transport-grpc
```

```php
// AppServiceProvider::boot()
use FlavioMoreir4\Transfeera\Events\TransfeeraRequestComplete;
use Illuminate\Support\Facades\Event;
use OpenTelemetry\API\Globals;

Event::listen(TransfeeraRequestComplete::class, function ($event) {
    static $tracer;

    if ($tracer === null) {
        $tracer = Globals::tracerProvider()
            ->getTracer('laravel-transfeera');
    }

    $span = $tracer->spanBuilder('transfeera.api')
        ->setSpanKind(SpanKind::KIND_CLIENT)
        ->startSpan();

    $span->setAttribute('http.method', $event->method);
    $span->setAttribute('http.url', $event->url);
    $span->setAttribute('http.status_code', $event->status);
    $span->setAttribute('http.request.duration_ms',
        round($event->duration * 1000, 2));
    $span->setAttribute('transfeera.domain', $event->domain);

    $span->end();
});
```

### Prometheus — Métricas

Para expor métricas no formato Prometheus:

```bash
composer require promphp/prometheus_client_php
```

```php
// AppServiceProvider::boot()
use FlavioMoreir4\Transfeera\Events\TransfeeraRequestComplete;
use Illuminate\Support\Facades\Event;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

Event::listen(TransfeeraRequestComplete::class, function ($event) {
    static $registry;
    static $histogram;
    static $counter;

    if ($registry === null) {
        $registry = new CollectorRegistry(new InMemory());
        $histogram = $registry->registerHistogram(
            'transfeera',
            'request_duration_seconds',
            'Duração das requisições à API Transfeera',
            ['domain', 'method', 'status'],
            [0.01, 0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0],
        );
        $counter = $registry->registerCounter(
            'transfeera',
            'requests_total',
            'Total de requisições à API Transfeera',
            ['domain', 'method', 'status'],
        );
    }

    $labels = [
        'domain' => $event->domain,
        'method' => $event->method,
        'status' => (string) $event->status,
    ];

    $histogram->observe($event->duration, $labels);
    $counter->inc($labels);
});
```

### Grafana — Dashboard JSON

Importe este dashboard no Grafana para visualizar as métricas da Transfeera:

```json
{
  "title": "Transfeera API",
  "panels": [
    {
      "title": "Requisições por minuto",
      "type": "graph",
      "targets": [{
        "expr": "rate(transfeera_requests_total[1m])",
        "legendFormat": "{{domain}} {{method}}"
      }]
    },
    {
      "title": "Duração P95 (ms)",
      "type": "graph",
      "targets": [{
        "expr": "histogram_quantile(0.95, rate(transfeera_request_duration_seconds_bucket[5m])) * 1000",
        "legendFormat": "{{domain}}"
      }]
    },
    {
      "title": "Taxa de erro (%)",
      "type": "stat",
      "targets": [{
        "expr": "sum(rate(transfeera_requests_total{status=~\"5..\"}[5m])) / sum(rate(transfeera_requests_total[5m])) * 100"
      }]
    },
    {
      "title": "Status code distribution",
      "type": "piechart",
      "targets": [{
        "expr": "sum(transfeera_requests_total) by (status)"
      }]
    }
  ]
}
```

### Prometheus — Recording Rules

```yaml
# transfeera_rules.yml
groups:
  - name: transfeera
    rules:
      - record: transfeera:error_rate:5m
        expr: |
          sum(rate(transfeera_requests_total{status=~"5.."}[5m]))
          /
          sum(rate(transfeera_requests_total[5m]))
      - record: transfeera:p95_duration_ms:5m
        expr: |
          histogram_quantile(0.95,
            rate(transfeera_request_duration_seconds_bucket[5m])
          ) * 1000
      - alert: TransfeeraHighErrorRate
        expr: transfeera:error_rate:5m > 0.05
        for: 5m
        labels:
          severity: critical
        annotations:
          summary: "Taxa de erro Transfeera acima de 5%"
```

### Comando de diagnóstico

Use `php artisan transfeera:debug` para verificar a configuração:

```bash
$ php artisan transfeera:debug

🔬 Transfeera SDK — Diagnóstico Detalhado

📦 Ambiente
  PHP:       8.4.6
  Laravel:   13.0.0
  Ambiente:  local
  Debug:     true

⚙️ Configuração
  Environment:     sandbox ✅
  Client ID:       test*****2345 ✅
  Client Secret:   ****cret ✅
  Timeout:         30s
  Retry:           3x a cada 100ms
  Cache Store:     file
  User-Agent:      Laravel App (contato@exemplo.com)
  mTLS:            sandbox — não requerido ℹ️

🌐 URLs Base
  Autenticação:   https://login-api-sandbox.transfeera.com
  Pagamentos:     https://api-sandbox.transfeera.com
  (...)
```

---

## Tratamento de Erros

Sempre capture exceções específicas para responder adequadamente:

```php
use FlavioMoreir4\Transfeera\Exceptions\{
    TransfeeraValidationException,
    TransfeeraAuthenticationException,
    TransfeeraRateLimitException,
    PaymentException,
    ReceivableException,
};

try {
    $batch = Transfeera::batches()->create($data);
} catch (TransfeeraValidationException $e) {
    // Dados inválidos — $e->getErrors() retorna array de erros
    Log::warning('Dados inválidos', ['errors' => $e->getErrors()]);
} catch (TransfeeraAuthenticationException $e) {
    // Credenciais inválidas/expiradas
    Log::error('Falha de autenticação');
} catch (TransfeeraRateLimitException $e) {
    // Rate limit excedido — $e->getRetryAfter() retira segundos
    $wait = $e->getRetryAfter() ?? 30;
    sleep($wait);
    // Retentar...
} catch (PaymentException $e) {
    // Erro específico de pagamentos
    Log::error('Erro no pagamento', [
        'status' => $e->getStatusCode(),
        'message' => $e->getMessage(),
    ]);
}
```

---

## Boas Práticas

1. **Sempre use centavos (int)** para valores monetários — nunca float.
2. **Configure mTLS em produção** via `TRANSFEERA_MTLS_CERT_PATH` e `TRANSFEERA_MTLS_KEY_PATH`.
3. **Use o comando `transfeera:debug`** para verificar a configuração antes de integrar.
4. **Monitore rate limits** com as headers `X-RateLimit-Remaining` expostas nas exceptions.
5. **Valide webhooks** com `verifySignature()` antes de processar o payload.
6. **Use Hub de Contas** para operar múltiplos clientes com uma única credencial.
7. **Cache do token** é automático — o SDK gerencia renovação com lock.
