# Pix Automático

Guia completo para implementação de cobranças recorrentes via Pix Automático.

## Visão Geral

O Pix Automático permite criar autorizações de cobrança recorrente entre um pagador e um recebedor. Uma vez autorizada, o recebedor pode gerar instruções de pagamento (Payment Intents) sem necessidade de nova autenticação do pagador.

### Fluxo Completo

```
┌──────────┐     ┌─────────────┐     ┌──────────────┐
│  Pagador  │────▶│ Autorização │────▶│ Payment Intent│
│ autoriza  │     │  (active)   │     │  (completed)  │
└──────────┘     └──────┬──────┘     └──────┬───────┘
                        │                   │
                        ▼                   ▼
                   ┌──────────┐      ┌──────────────┐
                   │ Cancelar │      │   Cancelar   │
                   │   auth   │      │   intent     │
                   └──────────┘      └──────────────┘
```

## Autorizações (AuthorizationResource)

### Criar Autorização

```php
$auth = Transfeera::pixAutomaticoAuthorizations()->create([
    'payer_pix_key' => 'fulano@email.com',
    'limit_value' => 500000, // R$ 5.000,00 em centavos
    'split_payment' => [
        ['key' => 'empresa@email.com', 'value_percent' => 70],
        ['key' => 'parceiro@email.com', 'value_percent' => 30],
    ],
]);

echo $auth->id; // auth_abc123
```

### Listar Autorizações

```php
$authorizations = Transfeera::pixAutomaticoAuthorizations()->list([
    'status' => 'active',
    'page' => 1,
    'per_page' => 20,
]);

foreach ($authorizations as $auth) {
    echo "{$auth->id}: {$auth->status} - {$auth->limitValue}";
}
```

### Consultar Autorização

```php
$auth = Transfeera::pixAutomaticoAuthorizations()->get('auth_abc123');
echo $auth->payerPixKey; // Chave Pix do pagador
```

### Cancelar Autorização

```php
$result = Transfeera::pixAutomaticoAuthorizations()->cancel('auth_abc123');
echo $result->success ? 'Cancelado' : 'Falha';
```

### Consultar Cancelamento

```php
$cancellation = Transfeera::pixAutomaticoAuthorizations()->getCancellation(
    'auth_abc123', 
    'cancel_xyz'
);
echo $cancellation->status;
```

### Atualizar Split

```php
Transfeera::pixAutomaticoAuthorizations()->update('auth_abc123', [
    'split_payment' => [
        ['key' => 'empresa@email.com', 'value_percent' => 100],
    ],
]);
```

## Instruções de Pagamento (PaymentIntentResource)

### Criar Instrução (Cobrança)

```php
$intent = Transfeera::pixAutomaticoPaymentIntents()->create('auth_abc123', [
    'value' => 15000, // R$ 150,00
    'description' => 'Assinatura Mensal',
    'due_date' => '2025-08-15',
]);

echo $intent->id; // pi_abc123
```

### Listar Instruções

```php
$intents = Transfeera::pixAutomaticoPaymentIntents()->list([
    'status' => 'pending',
]);

foreach ($intents as $intent) {
    echo "{$intent->id}: R$ " . ($intent->value / 100);
}
```

### Consultar Instrução

```php
$intent = Transfeera::pixAutomaticoPaymentIntents()->get('pi_abc123');
echo $intent->authorizationId; // ID da autorização pai
```

### Cancelar Instrução

```php
$result = Transfeera::pixAutomaticoPaymentIntents()->cancel('pi_abc123');
echo $result->success ? 'Instrução cancelada' : 'Falha ao cancelar';
```

### Reenviar Retentativa

```php
$result = Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_abc123');
echo $result->status;
```

## Tratamento de Erros

```php
use FlavioMoreir4\Transfeera\Exceptions\PixAutomaticoException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;

try {
    $auth = Transfeera::pixAutomaticoAuthorizations()->create([
        'payer_pix_key' => 'invalido',
    ]);
} catch (PixAutomaticoException $e) {
    echo "Erro Pix Automático: {$e->getMessage()}";
} catch (TransfeeraValidationException $e) {
    foreach ($e->getErrors() as $field => $errors) {
        echo "$field: " . implode(', ', $errors);
    }
}
```

## Webhooks

Configure webhooks para receber notificações sobre eventos do Pix Automático:

| Evento | Descrição |
|--------|-----------|
| `authorization.created` | Nova autorização criada |
| `authorization.cancelled` | Autorização cancelada |
| `payment_intent.created` | Instrução de pagamento criada |
| `payment_intent.completed` | Pagamento concluído |
| `payment_intent.failed` | Pagamento falhou |

## Referência

- [Documentação Oficial Pix Automático](https://docs.transfeera.dev/reference/pix-automatico)
- [Autorizações](https://docs.transfeera.dev/reference/automatic-pix-authorizations)
- [Payment Intents](https://docs.transfeera.dev/reference/automatic-pix-payment-intents)
