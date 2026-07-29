# Laravel Transfeera

[![Latest Version](https://img.shields.io/packagist/v/flaviomoreir4/laravel-transfeera.svg)](https://packagist.org/packages/flaviomoreir4/laravel-transfeera)
[![PHP](https://img.shields.io/badge/PHP-8.2%2B-blue)](https://php.net)
[![License](https://img.shields.io/github/license/flaviomoreir4/laravel-transfeera)](LICENSE)

SDK Laravel para integração completa com a API **Transfeera** — Pagamentos, Recebimentos, Pix Automático, Conta Certa, Hub de Contas e MED/Infrações.

> 🚧 Projeto em desenvolvimento faseado. Fases 1, 2, 3 e 4 concluídas — consulte a [tabela de cobertura](#cobertura-de-endpoints).

## Requisitos

- PHP 8.2+
- Laravel 11+
- Composer

## Instalação

```bash
composer require flaviomoreir4/laravel-transfeera
```

### Configuração rápida

```bash
php artisan transfeera:install
```

Isso publica o arquivo `config/transfeera.php` e valida o ambiente.

### Variáveis de ambiente (.env)

```env
TRANSFEERA_ENVIRONMENT=sandbox
TRANSFEERA_CLIENT_ID=seu_client_id
TRANSFEERA_CLIENT_SECRET=seu_client_secret
TRANSFEERA_USER_AGENT="MeuApp (email@exemplo.com)"

# Apenas em produção (mTLS obrigatório para Pagamentos e Conta Certa):
TRANSFEERA_MTLS_CERT_PATH=/caminho/para/certificado.pem
TRANSFEERA_MTLS_KEY_PATH=/caminho/para/chave.pem
```

---

## Uso

### Via Facade

```php
use Transfeera;
```

#### Pagamentos

```php
// Lotes
$batch = Transfeera::batches()->create(['name' => 'Pagamentos Fornecedores']);
$batch = Transfeera::batches()->get('batch_123');
$batches = Transfeera::batches()->list(['page' => 1]);
$batch = Transfeera::batches()->update('batch_123', ['name' => 'Novo Nome']);
Transfeera::batches()->delete('batch_123');
Transfeera::batches()->process('batch_123'); // Fechar lote

// Transferências
$transfer = Transfeera::transfers()->create('batch_123', [
    'amount' => 15000, // R$ 150,00 em centavos
    'pix_key' => 'fulano@email.com',
    'pix_key_type' => 'email',
]);
$transfers = Transfeera::transfers()->list('batch_123');

// Boletos
$billet = Transfeera::billets()->create($data);
$billet = Transfeera::billets()->get('billet_456');

// Bancos
$banks = Transfeera::banks()->list();

// Saldo e extrato
$balance = Transfeera::statement()->getBalance();
$report = Transfeera::statement()->requestReport([
    'data_inicio' => '2025-01-01',
    'data_fim' => '2025-01-31',
]);

// Pix
$pixData = Transfeera::pix()->lookupKey('fulano@email.com');
$parsed = Transfeera::pix()->parseEmv('00020126580014BR.GOV.BCB.PIX...');

// Recorrências
$recurrences = Transfeera::recurrences()->list();
Transfeera::recurrences()->cancel('rec_789');
```

#### Recebimentos

```php
// Chaves Pix
$keys = Transfeera::pixKeys()->list();
$key = Transfeera::pixKeys()->create(['type' => 'cpf', 'value' => '12345678909']);
Transfeera::pixKeys()->verify('key_abc', '123456');
Transfeera::pixKeys()->delete('key_abc');

// Portabilidade de chave Pix
$claim = Transfeera::pixKeys()->claim('1199999999');
Transfeera::pixKeys()->confirmClaim('claim_1');
Transfeera::pixKeys()->cancelClaim('claim_1');

// QR Codes Pix
$static = Transfeera::pixQrCodes()->createStatic(['key' => 'email@example.com']);
$immediate = Transfeera::pixQrCodes()->createImmediate(['key' => 'email@example.com', 'value' => 5000]);
$due = Transfeera::pixQrCodes()->createDue(['key' => 'email@example.com', 'value' => 10000, 'due_date' => '2025-12-31']);
Transfeera::pixQrCodes()->revoke('qr_1');

// Cash-in (Pix recebidos)
$pixList = Transfeera::pixCashIn()->list(['start_date' => '2025-01-01']);
$pix = Transfeera::pixCashIn()->getByEnd2EndId('E2E123');
$refund = Transfeera::pixCashIn()->requestRefund('E2E123', ['amount' => 5000]);

// Cobranças (boleto + Pix)
$charge = Transfeera::charges()->create(['payer_name' => 'João Silva', 'value' => 5000]);
$charges = Transfeera::charges()->list(['status' => 'pending']);
Transfeera::charges()->cancel('chg_1');
$pdf = Transfeera::charges()->downloadPdf('chg_1');

// Links de pagamento
$link = Transfeera::paymentLinks()->create(['name' => 'Produto X', 'value' => 1990]);
Transfeera::paymentLinks()->delete('pl_1');
```

#### Pix Automático

```php
// Autorizações
$auth = Transfeera::pixAutomaticoAuthorizations()->create([
    'payer_pix_key' => 'fulano@email.com',
    'limit_value' => 50000,
]);
$auths = Transfeera::pixAutomaticoAuthorizations()->list(['status' => 'active']);
Transfeera::pixAutomaticoAuthorizations()->cancel('auth_1');
Transfeera::pixAutomaticoAuthorizations()->update('auth_1', [
    'split_payment' => ['percentage' => 50],
]);

// Instruções de pagamento (Payment Intents)
$intent = Transfeera::pixAutomaticoPaymentIntents()->create('auth_1', [
    'value' => 15000,
    'description' => 'Mensalidade',
]);
Transfeera::pixAutomaticoPaymentIntents()->cancel('pi_1');
Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_1');
```

#### Webhooks

```php
// Pagamentos
$url = Transfeera::paymentsWebhooks()->createUrl(['url' => 'https://meudominio.com/webhook']);
$urls = Transfeera::paymentsWebhooks()->listUrls();
$events = Transfeera::paymentsWebhooks()->listEvents(['status' => 'failed']);
Transfeera::paymentsWebhooks()->resendEvent('evt_1');

// Recebimentos
$url = Transfeera::receivablesWebhooks()->createUrl(['url' => 'https://meudominio.com/webhook-rec']);
$events = Transfeera::receivablesWebhooks()->listEvents();

// Conta Certa
$url = Transfeera::contaCertaWebhooks()->createUrl(['url' => 'https://meudominio.com/webhook-cc']);
$events = Transfeera::contaCertaWebhooks()->listEvents();
```

#### Conta Certa / Validações

```php
// Validar conta bancária
$validation = Transfeera::contaCertaValidations()->create([
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789',
    'document' => '12345678909',
    'account_type' => 'checking',
]);
$validations = Transfeera::contaCertaValidations()->list(['status' => 'completed']);
$validation = Transfeera::contaCertaValidations()->get('val_123');

// Bancos suportados pela Conta Certa
$banks = Transfeera::contaCertaBanks()->list();
```

#### Hub de Contas

```php
// Gerenciar contas digitais
$account = Transfeera::accounts()->create([
    'name' => 'Empresa XYZ',
    'document' => '11222333444455',
    'email' => 'financeiro@xyz.com',
]);
$accounts = Transfeera::accounts()->list();
$account = Transfeera::accounts()->get('acc_123');
Transfeera::accounts()->close('acc_123'); // Remove chaves Pix vinculadas

// Operar em nome de uma conta específica
$batch = Transfeera::batches('acc_123')->create(['name' => 'Lote da Conta 123']);
```

#### MED / Infrações

```php
// Infrações (Mecanismo Especial de Devolução)
$infractions = Transfeera::infractions()->list();
$infraction = Transfeera::infractions()->get('inf_123');

// Enviar análise individual
Transfeera::infractions()->submitAnalysis([
    'infraction_id' => 'inf_123',
    'type' => 'refund',
    'refund_amount' => 5000, // R$ 50,00 em centavos
    'description' => 'Devolução por acordo entre as partes',
]);

// Enviar análise em lote
Transfeera::infractions()->submitBatchAnalysis([
    ['infraction_id' => 'inf_001', 'type' => 'refund', 'refund_amount' => 3000],
    ['infraction_id' => 'inf_002', 'type' => 'contest', 'description' => 'Pagamento correto'],
]);
```

```php
// Validação de assinatura (no controller do webhook)
$validator = new \FlavioMoreir4\Transfeera\Webhooks\SignatureValidator(
    secret: config('transfeera.webhook_secret'),
    isReceivables: true,
);

if (! $validator->isValid($request->getContent(), $request->header('X-Signature'))) {
    abort(401, 'Invalid signature');
}

// Disparar evento Laravel
\FlavioMoreir4\Transfeera\Webhooks\WebhookEvent::dispatch(
    domain: 'payments',
    type: 'batch.processed',
    payload: ['batch_id' => '123'],
);
```

### Via injeção de dependência

```php
use FlavioMoreir4\Transfeera\TransfeeraClient;

class PagamentoService
{
    public function __construct(
        private readonly TransfeeraClient $transfeera,
    ) {}

    public function pagar(array $data): array
    {
        $batch = $this->transfeera->batches()->create([
            'name' => 'Pagamento via Service',
        ]);

        return $this->transfeera->transfers()->create($batch['id'], $data);
    }
}
```

### Hub de Contas (múltiplas contas digitais)

```php
// Passando accountId, o TokenManager adiciona scope=account_id:{id}
$batch = Transfeera::batches('conta_digital_123')->create([
    'name' => 'Lote da Conta 123',
]);
```

Todas as chamadas aceitam `?string $accountId = null` como último parâmetro.

---

## Cobertura de endpoints

### Fase 1 ✅ — Núcleo + Pagamentos

| Recurso | Status |
|---------|--------|
| Service Provider & Config | ✅ |
| TokenManager (client_credentials + cache + lock) | ✅ |
| Connector com mTLS condicional | ✅ |
| Mapeamento de erros HTTP (401/422/429) | ✅ |
| Artisan command `transfeera:install` | ✅ |
| Bancos — listar | ✅ |
| Lotes — CRUD + processar (fechar) | ✅ |
| Transferências — CRUD | ✅ |
| Boletos — CRUD + consulta CIP | ✅ |
| Saldo/Extrato — consultar saldo, resgatar, relatório | ✅ |
| Pix — consulta DICT + parse EMV | ✅ |
| Recorrências — listar, listar pagamentos, cancelar | ✅ |

### Fase 2 ✅ — Recebimentos

| Recurso | Status |
|---------|--------|
| Chaves Pix — CRUD, verificação, portabilidade (claim/confirm/cancel) | ✅ |
| QR Codes Pix — estático, cobrança imediata, com vencimento, revogar | ✅ |
| Pix recebidos (Cash-in) — listar por período, consultar por end2endId | ✅ |
| Devoluções Pix — solicitar devolução, consultar devoluções | ✅ |
| Cobranças (boleto + Pix) — CRUD, cancelar, download PDF | ✅ |
| Links de pagamento — criar, consultar, excluir | ✅ |

### Fase 3 ✅ — Pix Automático + Webhooks

| Recurso | Status |
|---------|--------|
| Autorizações Pix Automático — CRUD + cancelar + atualizar split | ✅ |
| Payment Intents — CRUD + cancelar + reenviar retentativa | ✅ |
| Webhooks Pagamentos — URL CRUD + eventos + reenvio | ✅ |
| Webhooks Recebimentos — URL CRUD + eventos + reenvio | ✅ |
| Webhooks Conta Certa — URL CRUD + eventos + reenvio | ✅ |
| SignatureValidator — HMAC-SHA256 com suporte a pagamentos/recebimentos | ✅ |
| WebhookEvent — evento Laravel dispatchable | ✅ |

### Fase 4 ✅ — Conta Certa + Hub de Contas + MED

| Recurso | Status |
|---------|--------|
| Validações Conta Certa — criar, listar, consultar | ✅ |
| Bancos Conta Certa — listar | ✅ |
| Hub de Contas — criar, listar, consultar, encerrar | ✅ |
| MED/Infrações — listar, consultar, enviar análise individual e em lote | ✅ |

---

## Testes

```bash
composer test
```

Testes com Pest, usando `Http::fake()` com payloads mockados. Atualmente **84 testes, 113 asserções** — todos passando.

```bash
composer test-coverage
```

## Análise estática

```bash
composer phpstan       # PHPStan nível 8
composer rector        # Rector dry-run
composer rector-fix    # Rector com correção automática
```

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md) para o histórico de alterações.

## Contribuindo

Veja [CONTRIBUTING.md](CONTRIBUTING.md) para o padrão de commits, branches e PRs.

## Licença

MIT — veja o arquivo [LICENSE](LICENSE) para detalhes.
