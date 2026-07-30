# ADR-007: Validação de Webhook com HMAC-SHA256

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

A Transfeera envia notificações via webhook com assinatura HMAC-SHA256 no header `X-Signature`. O SDK precisa validar a assinatura para garantir que o payload não foi adulterado e que a origem é legítima.

## Decisão

Criar `SignatureValidator` que:
1. **Suporta dois algoritmos**: pagamentos/conta certa usam `isValid()`, recebimentos usam `isValidForReceivables()` (a regra de cálculo difere entre domínios)
2. **Usa `hash_equals()`** — comparação timing-safe para evitar ataques de timing
3. **Secrets configuráveis por domínio** — `webhook_secrets.payments`, `webhook_secrets.receivables`, `webhook_secrets.conta_certa`, com fallback para `webhook_secret` global

```php
// No WebhookController:
$secret = config("transfeera.webhook_secrets.{$domain}", config('transfeera.webhook_secret'));

$validator = new SignatureValidator($secret);
$isValid = $domain === 'receivables'
    ? $validator->isValidForReceivables($payload, $signature)
    : $validator->isValid($payload, $signature);
```

O controller dispara `TransfeeraWebhookReceived::dispatch()` após validação bem-sucedida.

## Consequências

**Positivas:**
- Segurança: assinatura validada antes de qualquer processamento
- Timing-safe: `hash_equals()` previne ataques de timing
- Flexibilidade: secrets diferentes por domínio ou um único global
- Integração: evento Laravel permite listeners desacoplados

**Negativas:**
- Duplicação entre `isValid()` e `isValidForReceivables()` (ambos fazem HMAC-SHA256, mas podem divergir no futuro)
- Controller retorna 500 se secret não configurado — poderia ser 401
- TransfeeraWebhookReceived e WebhookEvent coexistem (duplicação)
