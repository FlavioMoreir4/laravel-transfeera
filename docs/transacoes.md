# Transações Financeiras

Guia completo do fluxo de transações financeiras: lotes, transferências, boletos, saldo e conciliação.

## Visão Geral

O fluxo financeiro típico segue este pipeline:

```
┌──────────┐   ┌──────────────┐   ┌─────────────┐   ┌──────────┐
│  Lote    │──▶│ Transferências│──▶│  Fechar     │──▶│  Extrato │
│  criar   │   │  + Boletos   │   │  (process)  │   │  Saldo   │
└──────────┘   └──────────────┘   └──────┬──────┘   └──────────┘
                                         │
                                         ▼
                                   ┌──────────┐
                                   │  Relatório│
                                   │  finance. │
                                   └──────────┘
```

## 1. Lotes de Pagamento (BatchResource)

### Criar Lote

```php
$batch = Transfeera::batches()->create([
    'name' => 'Pagamentos Fornecedores - Julho/2025',
]);

echo $batch->id; // batch_abc123
```

### Adicionar Transferências

```php
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;
use FlavioMoreir4\Transfeera\DTOs\BilletDTO;

// Transferência Pix
$transfer = Transfeera::transfers()->create(
    'batch_abc123',
    new TransferDTO(
        pixKey: 'fulano@email.com',
        amount: 150000, // R$ 1.500,00
        description: 'Pagamento fornecedor',
    ),
);

// Boleto
$billet = Transfeera::billets()->create(
    'batch_abc123',
    [
        'billet_number' => '001',
        'value' => 50000,
        'beneficiary_name' => 'João Silva',
        'beneficiary_document' => '12345678909',
        'due_date' => '2025-08-15',
    ],
);
```

### Listar Transferências

```php
$transfers = Transfeera::transfers()->list('batch_abc123', [
    'status' => 'pending',
]);
```

### Processar (Fechar) Lote

```php
$batch = Transfeera::batches()->process('batch_abc123');
echo $batch->status; // processing
```

### Remover Transferência

```php
$result = Transfeera::transfers()->delete('batch_abc123', 'trf_xyz');
```

### Remover Boleto

```php
$result = Transfeera::billets()->delete('batch_abc123', 'bil_xyz');
```

## 2. Boletos Avulsos (Fora de Lote)

```php
// Criar boleto avulso
$billet = Transfeera::billets()->createStandalone([
    'value' => 75000, // R$ 750,00
    'beneficiary_name' => 'Maria Souza',
    'beneficiary_document' => '98765432100',
    'due_date' => '2025-09-01',
]);

// Consultar CIP
$cip = Transfeera::billets()->consultCip($billet->id);
echo $cip->cipStatus; // registered

// Remover boleto avulso
$result = Transfeera::billets()->deleteStandalone('bil_xyz');
```

## 3. Extrato e Saldo (StatementResource)

### Consultar Saldo

```php
$statement = Transfeera::statements()->balance();
echo "Disponível: R$ " . ($statement->balance / 100);
echo "Bloqueado: R$ " . ($statement->blocked / 100);
echo "Total: R$ " . ($statement->total / 100);
```

### Solicitar Saque

```php
$withdraw = Transfeera::statements()->withdraw([
    'amount' => 100000, // R$ 1.000,00
    'pix_key' => 'empresa@email.com',
]);

echo $withdraw->status; // processing
```

### Solicitar Relatório

```php
// Solicitar
$report = Transfeera::statements()->requestReport(
    '2025-01-01',
    '2025-07-30',
);
echo $report->status; // processing

// Consultar status
$report = Transfeera::statements()->getReport($report->id);
if ($report->status === 'completed') {
    echo "Download: {$report->url}";
}
```

## 4. Cobranças (ChargeResource) — Boleto + Pix

### Criar Cobrança

```php
$charge = Transfeera::charges()->create([
    'payer_name' => 'João Silva',
    'payer_document' => '12345678909',
    'value' => 10000, // R$ 100,00
    'due_date' => '2025-08-20',
]);

echo $charge->billetBarcode; // Código de barras
echo $charge->pixEmv; // Pix Copia e Cola
```

### Cancelar Cobrança

```php
$result = Transfeera::charges()->cancel('charge_abc123');
echo $result->status; // cancelled
```

### Baixar PDF

```php
use Illuminate\Support\Facades\Http;

// ChargeResource::downloadPdf() faz download do comprovante
$response = Transfeera::charges()->downloadPdf('charge_abc123', 'rec_xyz');
// $response contém o binário do PDF
```

## 5. Recorrências

### Listar Recorrências

```php
$recurrences = Transfeera::recurrences()->list();
foreach ($recurrences as $rec) {
    echo "{$rec->name}: R$ " . ($rec->value / 100);
}
```

### Listar Pagamentos da Recorrência

```php
$payments = Transfeera::recurrences()->listPayments('rec_abc123');
foreach ($payments as $payment) {
    echo "Pagamento {$payment->id}: R$ " . ($payment->value / 100) . " - {$payment->status}";
}
```

### Cancelar Recorrência

```php
$result = Transfeera::recurrences()->cancel('rec_abc123');
echo $result->success ? 'Recorrência cancelada' : 'Falha';
```

## Tratamento de Erros

```php
use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;

try {
    $batch = Transfeera::batches()->create(['name' => 'Lote Teste']);
} catch (PaymentException $e) {
    Log::error('Erro em pagamentos', [
        'message' => $e->getMessage(),
    ]);
} catch (TransfeeraRateLimitException $e) {
    sleep($e->getRetryAfter());
    // Retentar...
}
```

## Boas Práticas

1. **Sempre use centavos (inteiros)** para valores monetários — nunca float
2. **Crie o lote primeiro**, adicione transferências/boletos, depois processe
3. **Consulte o saldo** antes de agendar pagamentos
4. **Use filas** para processamento em lote (`TransfeeraBaseJob`)
5. **Monitore rate limits** com `RateLimitMonitor`
6. **Webhooks** são mais confiáveis que polling para status de pagamentos

## Referência

- [Pagamentos — Documentação Oficial](https://docs.transfeera.dev/reference/payments)
- [Lotes](https://docs.transfeera.dev/reference/batch)
- [Transferências](https://docs.transfeera.dev/reference/transfers)
- [Boletos](https://docs.transfeera.dev/reference/billets)
- [Extrato](https://docs.transfeera.dev/reference/statement)
