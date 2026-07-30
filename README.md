# Laravel Transfeera

[![Latest Version](https://img.shields.io/packagist/v/flaviomoreir4/laravel-transfeera.svg)](https://packagist.org/packages/flaviomoreir4/laravel-transfeera)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x%2F13.x-FF2D20)](https://laravel.com)
[![License](https://img.shields.io/github/license/flaviomoreir4/laravel-transfeera)](LICENSE)
[![CI](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml/badge.svg)](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/badge/tests-118%2F118-brightgreen)](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon)
[![Rector](https://img.shields.io/badge/Rector-clean-brightgreen)](rector.php)

SDK Laravel para integração completa com a API **Transfeera** — Pagamentos, Recebimentos, Pix Automático, Conta Certa, Hub de Contas e MED/Infrações.

> 🚧 Projeto em desenvolvimento faseado. Versão atual: **v1.7.0** — consulte a [tabela de cobertura](#cobertura-de-endpoints).

---

## Documentação

| Guia | Descrição |
|------|-----------|
| [Primeiro Pagamento](docs/primeiro-pagamento.md) | Passo a passo: instalação → configuração → lote → transferência → webhooks |
| [Primeiro Recebimento](docs/primeiro-recebimento.md) | Chaves Pix, QR Codes (estático/imediato/vencimento), Cobranças, Links, Cash-in |
| [Pagamentos](docs/pagamentos.md) | Lotes, transferências, boletos, bancos, saldo/extrato, Pix DICT, recorrências |
| [Recebimentos](docs/recebimentos.md) | Chaves Pix, QR Codes, Cobranças (boleto+Pix), Links, Cash-in, Devoluções |
| [Pix Automático](docs/pix-automatico.md) | Autorizações, Payment Intents, split, webhooks |
| [Conta Certa](docs/conta-certa.md) | Validações de conta, bancos suportados |
| [Hub de Contas](docs/hub-contas.md) | Contas digitais, multi-tenancy via `accountId` |
| [MED/Infrações](docs/med.md) | Listar, consultar, análise individual e em lote |
| [Webhooks](docs/webhooks.md) | Rotas, secrets, HMAC-SHA256, eventos, listeners, retry, testes |
| [Tratamento de Erros](docs/erros.md) | Hierarquia de exceptions, códigos, handler global, retry, debug |
| [Exceptions](docs/exceptions.md) | Referência completa: hierarquia, quando catchar, métodos úteis |
| [Middlewares](docs/middlewares.md) | LoggingMiddleware, MetricsMiddleware, configuração, exportação Prometheus |
| [Changelog](docs/changelog.md) | Histórico versionado (Keep a Changelog) |

---

## Requisitos

- PHP 8.3+
- Laravel 12 / 13
- Composer

---

## Instalação

```bash
composer require flaviomoreir4/laravel-transfeera
```

### Configuração Rápida

```bash
php artisan transfeera:install
```

Isso publica o arquivo `config/transfeera.php` e valida o ambiente.

### Variáveis de Ambiente (`.env`)

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
use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;

// Lotes
$batch = Transfeera::batches()->create(new BatchDTO(
    name: 'Pagamentos Fornecedores',
    type: 'immediate', // ou 'scheduled'
));
$batch = Transfeera::batches()->get('batch_123');
$batches = Transfeera::batches()->list(['page' => 1]);
$batch = Transfeera::batches()->update('batch_123', ['name' => 'Novo Nome']);
Transfeera::batches()->delete('batch_123');
Transfeera::batches()->process('batch_123'); // Fechar lote

// Transferências
$transfer = Transfeera::transfers()->create('batch_123', new TransferDTO(
    amount: 15000, // R$ 150,00 em centavos
    pixKey: 'fulano@email.com',
    pixKeyType: 'email',
));
$transfer = Transfeera::transfers()->get('transfer_123');
$transfers = Transfeera::transfers()->list('batch_123');

// Bancos
$banks = Transfeera::banks()->list();

// Saldo e Extrato
$balance = Transfeera::statement()->getBalance();
$report = Transfeera::statement()->requestReport(new StatementReportDTO(
    startDate: '2025-01-01',
    endDate: '2025-01-31',
));

// Pix
$pixData = Transfeera::pix()->lookupKey('fulano@email.com');
$parsed = Transfeera::pix()->parseEmv('00020126580014BR.GOV.BCB.PIX...');

// Recorrências
$recurrences = Transfeera::recurrences()->list();
Transfeera::recurrences()->cancel('rec_789');
```

#### Recebimentos

```php
use FlavioMoreir4\Transfeera\DTOs\PixKeyDTO;
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;

// Chaves Pix
$keys = Transfeera::pixKeys()->list();
$key = Transfeera::pixKeys()->create(new PixKeyDTO(type: 'cpf', value: '12345678909'));
Transfeera::pixKeys()->verify('key_abc', '123456');
Transfeera::pixKeys()->delete('key_abc');

// Portabilidade
$claim = Transfeera::pixKeys()->claim('11999999999');
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
$refunds = Transfeera::pixCashIn()->getRefunds('E2E123');

// Cobranças (boleto + Pix)
$charge = Transfeera::charges()->create(new ChargeDTO(
    payerName: 'João Silva',
    value: 5000,
    dueDate: '2025-12-31',
));
$charges = Transfeera::charges()->list(['status' => 'pending']);
Transfeera::charges()->cancel('chg_1');
$pdf = Transfeera::charges()->downloadPdf('chg_1', 'rec_1');

// Links de pagamento
$link = Transfeera::paymentLinks()->create(new PaymentLinkDTO(
    name: 'Produto X',
    value: 1990,
    expiresIn: 30,
    redirectUrl: 'https://meuapp.com/sucesso',
));
Transfeera::paymentLinks()->delete('pl_1');
```

#### Pix Automático

```php
use FlavioMoreir4\Transfeera\DTOs\AuthorizationDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentIntentDTO;

// Autorizações
$auth = Transfeera::pixAutomaticoAuthorizations()->create(new AuthorizationDTO(
    payerPixKey: 'fulano@email.com',
    limitValue: 50000,
    startDate: '2025-01-01',
    endDate: '2025-12-31',
));
$auth = Transfeera::pixAutomaticoAuthorizations()->get('auth_1');
$auths = Transfeera::pixAutomaticoAuthorizations()->list(['status' => 'active']);
Transfeera::pixAutomaticoAuthorizations()->cancel('auth_1');
Transfeera::pixAutomaticoAuthorizations()->update('auth_1', [
    'split_payment' => ['percentage' => 50],
]);

// Instruções de Pagamento (Payment Intents)
$intent = Transfeera::pixAutomaticoPaymentIntents()->create('auth_1', new PaymentIntentDTO(
    value: 15000,
    description: 'Mensalidade',
    dueDate: '2025-12-31',
));
$intent = Transfeera::pixAutomaticoPaymentIntents()->get('pi_1');
Transfeera::pixAutomaticoPaymentIntents()->cancel('pi_1');
Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_1');
```

#### Webhooks

```php
// URLs de webhook são criadas via Resource
$url = Transfeera::paymentsWebhooks()->createUrl(['url' => 'https://meudominio.com/webhooks/transfeera/payments']);

// O pacote já expõe rotas prontas:
// POST /webhooks/transfeera/payments
// POST /webhooks/transfeera/receivables
// POST /webhooks/transfeera/conta-certa

// Basta ouvir o evento Laravel:
\FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived::class => [
    \App\Listeners\MeuWebhookListener::class,
],
```

#### Conta Certa / Validações

```php
use FlavioMoreir4\Transfeera\DTOs\ValidationDTO;

// Validar conta bancária
$validation = Transfeera::contaCertaValidations()->create(new ValidationDTO(
    bankCode: '341',
    agency: '1234',
    account: '56789',
    document: '12345678909',
    accountType: 'checking',
));
$validations = Transfeera::contaCertaValidations()->list(['status' => 'completed']);
$validation = Transfeera::contaCertaValidations()->get('val_123');

// Bancos suportados pela Conta Certa
$banks = Transfeera::contaCertaBanks()->list();
```

#### Hub de Contas

```php
use FlavioMoreir4\Transfeera\DTOs\AccountDTO;

// Gerenciar contas digitais
$account = Transfeera::accounts()->create(new AccountDTO(
    name: 'Empresa XYZ',
    document: '11222333444455',
    email: 'financeiro@xyz.com',
    phone: '11988887777',
));
$accounts = Transfeera::accounts()->list();
$account = Transfeera::accounts()->get('acc_123');
Transfeera::accounts()->close('acc_123'); // Remove chaves Pix vinculadas

// Operar em nome de uma conta específica
$batch = Transfeera::batches('acc_123')->create(['name' => 'Lote da Conta 123']);
```

#### MED / Infrações

```php
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;

// Infrações (MED - Mecanismo Especial de Devolução)
$infractions = Transfeera::infractions()->list();
$infraction = Transfeera::infractions()->get('inf_123');

// Enviar análise individual
Transfeera::infractions()->submitAnalysis('inf_123', new InfractionAnalysisDTO(
    type: 'refund',
    refundAmount: 50000, // R$ 500,00
    description: 'Devolução por acordo',
));

// Enviar análise em lote
Transfeera::infractions()->submitBatchAnalysis([
    new InfractionAnalysisDTO(type: 'refund', refundAmount: 30000),
    new InfractionAnalysisDTO(type: 'contest', description: 'Pagamento correto'),
]);
```

### Via Injeção de Dependência

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

## Documentação Avançada

- [Primeiro Pagamento](docs/primeiro-pagamento.md) — Passo a passo do primeiro pagamento
- [Primeiro Recebimento](docs/primeiro-recebimento.md) — Chaves Pix, QR Codes, cobranças
- [Pagamentos](docs/pagamentos.md) — Documentação completa de pagamentos
- [Recebimentos](docs/recebimentos.md) — Documentação completa de recebimentos
- [Pix Automático](docs/pix-automatico.md) — Autorizações, Payment Intents, split
- [Conta Certa](docs/conta-certa.md) — Validações, bancos suportados
- [Hub de Contas](docs/hub-contas.md) — Contas digitais, multi-tenancy
- [MED/Infrações](docs/med.md) — Infrações, análises individual/lote
- [Webhooks](docs/webhooks.md) — Rotas, secrets, HMAC-SHA256, eventos, listeners, retry, testes
- [Tratamento de Erros](docs/erros.md) — Exceptions, códigos, handler global, retry, jobs
- [Exceptions](docs/exceptions.md) — Referência completa: hierarquia, quando catchar, métodos úteis
- [Middlewares](docs/middlewares.md) — Logging, métricas, configuração, exportação Prometheus

---

## Cobertura de endpoints

| Fase | Domínio | Status | Resources |
|------|---------|--------|-----------|
| 1 | Núcleo + Pagamentos | ✅ | Batch, Transfer, Billet, Bank, Statement, Recurrence, Pix |
| 2 | Recebimentos | ✅ | PixKey, PixQrCode, PixCashIn, Charge, PaymentLink |
| 3 | Pix Automático + Webhooks | ✅ | Authorization, PaymentIntent, 3 WebhookResources |
| 4 | Conta Certa + Hub + MED | ✅ | Validation, Bank, Account, Infraction |

**Total: 24 Resources implementados**

---

## Testes

```bash
composer test
# OK (118 tests, 160 assertions)
```

```bash
composer test-coverage
```

Testes com Pest, usando `Http::fake()` com payloads mockados extraídos da documentação oficial. Atualmente **118 testes, 160 asserções** — todos passando.

---

## Análise Estática

```bash
composer phpstan       # PHPStan nível 8
composer rector        # Rector dry-run
composer rector-fix    # Rector com correção automática
composer format        # Pint/PSR-12
```

---

## Changelog

Veja [CHANGELOG.md](CHANGELOG.md) para o histórico de alterações.

---

## Documentação da API Transfeera

- [Endpoints](https://docs.transfeera.dev/reference/endpoints.md) — Referência completa da API
- [Guia de Pagamentos](https://docs.transfeera.dev/docs/pagamentos-lotes-como-funciona.md)
- [Guia de Recebimentos](https://docs.transfeera.dev/docs/recebimentos-introducao.md)
- [Guia de Pix Automático](https://docs.transfeera.dev/docs/pix-automatico-como-funciona.md)
- [Guia de Conta Certa](https://docs.transfeera.dev/docs/conta-certa-introducao.md)
- [Índice completo (llms.txt)](https://docs.transfeera.dev/llms.txt)

---

## Contribuindo

Veja [CONTRIBUTING.md](CONTRIBUTING.md) para o padrão de commits, branches, PRs e requisitos de qualidade.

---

## Licença

MIT — veja o arquivo [LICENSE](LICENSE) para detalhes.