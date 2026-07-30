# Middlewares — Laravel Transfeera

Este documento descreve os middlewares HTTP disponíveis no SDK para logging e métricas.

---

## Visão Geral

O SDK inclui dois middlewares opcionais que se integram ao `Connector` e podem ser habilitados via configuração:

| Middleware | Classe | Função |
|------------|--------|--------|
| **Logging** | `LoggingMiddleware` | Log de requests/responses com sanitização |
| **Métricas** | `MetricsMiddleware` | Contadores, histogramas, taxa de erro |

Ambos são registrados como singletons no `TransfeeraServiceProvider` e injetados no `Connector` automaticamente quando habilitados.

---

## LoggingMiddleware

Loga requests e responses HTTP com opções de sanitização, truncamento e níveis configuráveis.

### Configuração no `config/transfeera.php`

```php
'logging' => [
    'enabled' => env('TRANSFEERA_LOGGING_ENABLED', false),
    'level' => env('TRANSFEERA_LOGGING_LEVEL', 'info'),        // debug, info, warning, error
    'headers' => env('TRANSFEERA_LOGGING_HEADERS', false),    // log headers (sanitiza sensíveis)
    'body' => env('TRANSFEERA_LOGGING_BODY', false),          // log body (truncado)
    'max_body_length' => env('TRANSFEERA_LOGGING_MAX_BODY', 2000),
],
```

### No `.env`

```env
TRANSFEERA_LOGGING_ENABLED=true
TRANSFEERA_LOGGING_LEVEL=info
TRANSFEERA_LOGGING_HEADERS=false
TRANSFEERA_LOGGING_BODY=false
TRANSFEERA_LOGGING_MAX_BODY=2000
```

### O que é Logado

#### Request (antes de enviar)

```json
{
    "domain": "payments",
    "method": "POST",
    "url": "https://api-sandbox.transfeera.com/batch",
    "headers": {
        "authorization": "[REDACTED]",
        "content-type": "application/json",
        "user-agent": "Laravel Transfeera SDK"
    },
    "body": "{\"name\":\"Pagamentos Fornecedores\"}"
}
```

#### Response (após receber)

```json
{
    "domain": "payments",
    "method": "POST",
    "url": "https://api-sandbox.transfeera.com/batch",
    "status": 201,
    "duration_ms": 145.23,
    "successful": true,
    "response_headers": {
        "content-type": "application/json",
        "x-ratelimit-remaining": "99"
    },
    "response_body": "{\"id\":\"batch_123\",\"name\":\"Pagamentos Fornecedores\",\"status\":\"pending\"}"
}
```

### Sanitização Automática

Headers sensíveis são **sempre** redigidos (mesmo com `headers: true`):

| Header | Valor Logado |
|--------|--------------|
| `Authorization` | `[REDACTED]` |
| `X-Signature` | `[REDACTED]` |
| `Cookie` | `[REDACTED]` |
| `Set-Cookie` | `[REDACTED]` |

### Truncamento de Body

```php
// config/transfeera.php
'max_body_length' => 2000, // padrão: 2000 chars
```

Se o body exceder o limite:
```json
{
    "body": "{\"name\":\"Pagamentos... ... [truncated]"
}
```

### Níveis de Log

| Nível | Quando |
|-------|--------|
| `debug` | Todos requests/responses (verbose) |
| `info` | Responses successful (padrão) |
| `warning` | Responses 4xx |
| `error` | Responses 5xx / exceptions |

### Exemplo de Log Estruturado (Laravel)

```php
// config/logging.php
'channels' => [
    'transfeera' => [
        'driver' => 'daily',
        'path' => storage_path('logs/transfeera.log'),
        'level' => env('TRANSFEERA_LOG_LEVEL', 'info'),
    ],
],
```

---

## MetricsMiddleware

Coleta métricas em memória prontas para exportação (Prometheus, StatsD, DataDog, etc.).

### Configuração no `config/transfeera.php`

```php
'metrics' => [
    'enabled' => env('TRANSFEERA_METRICS_ENABLED', false),
    'prefix' => env('TRANSFEERA_METRICS_PREFIX', 'transfeera'),
],
```

### No `.env`

```env
TRANSFEERA_METRICS_ENABLED=true
TRANSFEERA_METRICS_PREFIX=transfeera
```

### Métricas Coletadas

| Métrica | Tipo | Labels | Descrição |
|---------|------|--------|-----------|
| `transfeera.http.requests` | Counter | domain, method, status | Total de requests |
| `transfeera.http.duration` | Histogram | domain, method | Latência em ms |
| `transfeera.http.errors` | Counter | domain, method, status | Erros 4xx/5xx |
| `transfeera.http.exceptions` | Counter | domain, method, exception | Exceptions por tipo |

### Acesso Programático

```php
use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;

// Em qualquer lugar da aplicação
$metrics = MetricsMiddleware::getMetrics();

/*
Retorna:
[
    'counters' => [
        'transfeera.http.requests|domain=payments|method=POST|status=201' => 42,
        'transfeera.http.errors|domain=payments|method=POST|status=422' => 3,
    ],
    'histograms' => [
        'transfeera.http.duration|domain=payments|method=POST' => [
            'count' => 42,
            'sum' => 6300.5,
            'min' => 120.1,
            'max' => 340.7,
            'avg' => 150.0,
            'p50' => 145.2,
            'p90' => 280.5,
            'p95' => 320.1,
            'p99' => 338.9,
        ],
    ],
    'gauges' => [],
]
*/

// Reset (útil para testes)
MetricsMiddleware::resetMetrics();
```

### Exportação para Prometheus (Exemplo)

```php
// routes/web.php
Route::get('/metrics', function () {
    $metrics = \FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware::getMetrics();
    
    $output = '';
    
    foreach ($metrics['counters'] as $key => $value) {
        $output .= "# TYPE {$key} counter\n";
        $output .= "{$key} {$value}\n";
    }
    
    foreach ($metrics['histograms'] as $key => $stats) {
        $output .= "# TYPE {$key} histogram\n";
        foreach ($stats as $stat => $value) {
            if ($stat === 'count') {
                $output .= "{$key}_count {$value}\n";
            } elseif ($stat === 'sum') {
                $output .= "{$key}_sum {$value}\n";
            } else {
                $output .= "{$key}{quantile=\"" . str_replace('p', '0.', $stat) . "\"} {$value}\n";
            }
        }
    }
    
    return response($output, 200, [
        'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
    ]);
});
```

### Integração com Prometheus Client (PHP)

```bash
composer require promphp/prometheus_client_php
```

```php
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;

$registry = new CollectorRegistry(new InMemory());

$requests = $registry->registerCounter(
    'transfeera', 'http_requests_total',
    'Total HTTP requests',
    ['domain', 'method', 'status']
);

$duration = $registry->registerHistogram(
    'transfeera', 'http_request_duration_seconds',
    'HTTP request duration',
    ['domain', 'method'],
    [0.05, 0.1, 0.25, 0.5, 1.0, 2.5, 5.0, 10.0]
);

// No MetricsMiddleware::handle() - adaptar para incrementar:
// $requests->incBy($value, ['domain' => $domain, 'method' => $method, 'status' => (string)$status]);
// $duration->observe($duration, ['domain' => $domain, 'method' => $method]);
```

---

## Habilitando Middlewares

### Via Config (Recomendado)

```php
// config/transfeera.php
return [
    // ...
    'logging' => [
        'enabled' => env('TRANSFEERA_LOGGING_ENABLED', false),
        'level' => env('TRANSFEERA_LOGGING_LEVEL', 'info'),
        'headers' => env('TRANSFEERA_LOGGING_HEADERS', false),
        'body' => env('TRANSFEERA_LOGGING_BODY', false),
        'max_body_length' => env('TRANSFEERA_LOGGING_MAX_BODY', 2000),
    ],
    'metrics' => [
        'enabled' => env('TRANSFEERA_METRICS_ENABLED', false),
        'prefix' => env('TRANSFEERA_METRICS_PREFIX', 'transfeera'),
    ],
];
```

### Via ServiceProvider (Programático)

```php
// App\Providers\AppServiceProvider.php
public function boot(): void
{
    $this->app->resolving(\FlavioMoreir4\Transfeera\Http\Connector::class, function ($connector, $app) {
        // Logging
        if (config('transfeera.logging.enabled')) {
            $logging = $app->make(\FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware::class);
            // O Connector já injeta automaticamente via ServiceProvider
        }
        
        // Métricas
        if (config('transfeera.metrics.enabled')) {
            $metrics = $app->make(\FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware::class);
        }
    });
}
```

---

## Exemplo Completo: Produção com Observabilidade

### `.env`

```env
# Logging
TRANSFEERA_LOGGING_ENABLED=true
TRANSFEERA_LOGGING_LEVEL=info
TRANSFEERA_LOGGING_HEADERS=false
TRANSFEERA_LOGGING_BODY=false
TRANSFEERA_LOGGING_MAX_BODY=2000

# Métricas
TRANSFEERA_METRICS_ENABLED=true
TRANSFEERA_METRICS_PREFIX=transfeera_prod

# Log channel dedicado
LOG_CHANNEL=stack
LOG_STACK=single,transfeera
```

### `config/logging.php`

```php
'channels' => [
    'transfeera' => [
        'driver' => 'daily',
        'path' => storage_path('logs/transfeera.log'),
        'level' => env('TRANSFEERA_LOG_LEVEL', 'info'),
        'days' => 30,
    ],
],
```

### Prometheus Scraping (docker-compose)

```yaml
# prometheus.yml
scrape_configs:
  - job_name: 'laravel-transfeera'
    static_configs:
      - targets: ['host.docker.internal:8000']
    metrics_path: '/metrics'
```

---

## Testes Reais (Extraídos do Suite)

```php
// tests/Unit/LoggingMiddlewareTest.php
test('log request e response', function () {
    $middleware = new LoggingMiddleware(
        enabled: true,
        logLevel: 'info',
        logHeaders: false,
        logBody: false,
    );

    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response([
            'id' => 'batch_123',
            'name' => 'Teste',
            'status' => 'pending',
        ], 201),
    ]);

    $request = Http::withToken('test-token')->post('api-sandbox.transfeera.com/batch', ['name' => 'Teste']);

    $response = $middleware->handle($request, fn ($req) => $req->send());

    expect($response->status())->toBe(201);
});

// tests/Unit/MetricsMiddlewareTest.php
test('coleta metricas basicas', function () {
    MetricsMiddleware::resetMetrics();

    $middleware = new MetricsMiddleware(enabled: true, prefix: 'test');

    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response(['id' => '1'], 201),
    ]);

    $request = Http::withToken('test')->post('api-sandbox.transfeera.com/batch', ['name' => 'Test']);
    $middleware->handle($request, fn ($req) => $req->send());

    $metrics = MetricsMiddleware::getMetrics();

    expect($metrics['counters'])->toHaveKey(
        'test.http.requests|domain=payments|method=POST|status=201'
    );
    expect($metrics['histograms']['test.http.duration|domain=payments|method=POST']['count'])->toBe(1);
});
```

---

## Boas Práticas

| Prática | Descrição |
|---------|-----------|
| **Logs em produção** | Use `info` para sucesso, `warning`/`error` para falhas; evite `debug` |
| **Métricas** | Habilite em produção; exporte via `/metrics` para Prometheus |
| **Secrets** | Nunca logue `Authorization`, `X-Signature`, `Cookie` — o middleware já redige |
| **Performance** | Métricas em memória têm overhead mínimo (<1ms/request) |
| **Retenção** | Configure rotação de logs (`daily`, 30 dias) e scrape interval do Prometheus (15-30s) |

---

## Roadmap (Documentado mas Não Implementado)

| Feature | Status |
|---------|--------|
| OpenTelemetry integration | 📋 Planejado |
| Distributed tracing (trace_id) | 📋 Planejado |
| Alerting rules pré-definidas | 📋 Planejado |
| Dashboard Grafana pré-configurado | 📋 Planejado |

---

## Links Úteis

- [Configuração](config/transfeera.php) — Referência completa
- [Connector](src/Http/Connector.php) — Onde middlewares são aplicados
- [ServiceProvider](src/TransfeeraServiceProvider.php) — Registro dos middlewares
- [Tratamento de Erros](erros.md) — Exceptions e retry