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

    // Exemplo: métrica Prometheus (com lib opcional)
    // prometheus_histogram('transfeera_request_duration_seconds')
    //     ->observe($event->duration, [
    //         'domain' => $event->domain,
    //         'method' => $event->method,
    //         'status' => (string) $event->status,
    //     ]);

    // Exemplo: span OpenTelemetry (com lib opcional)
    // $tracer->span('transfeera.api')
    //     ->setAttribute('http.method', $event->method)
    //     ->setAttribute('http.url', $event->url)
    //     ->setAttribute('http.status_code', $event->status)
    //     ->end();
});
```

### Middleware de logging

```php
// config/transfeera.php
'logging' => [
    'enabled' => true,
    'level' => 'info',       // 'debug', 'info', 'warning'
    'headers' => false,      // true para incluir payload no log
],
```

### Middleware de métricas

```php
// config/transfeera.php
'metrics' => [
    'enabled' => true,
    'prefix' => 'transfeera', // prefixo dos nomes das métricas
],
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
