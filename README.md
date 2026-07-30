<div align="center">

# Laravel Transfeera

**SDK Laravel oficial para integração completa com a API Transfeera**
<br>
Pagamentos • Recebimentos • Pix Automático • Conta Certa • Hub de Contas • MED

[![PHP](https://img.shields.io/badge/PHP-8.3%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x%2F13.x-FF2D20)](https://laravel.com)
[![License](https://img.shields.io/github/license/flaviomoreir4/laravel-transfeera)](LICENSE)
[![CI](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml/badge.svg)](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml)
[![Tests](https://img.shields.io/badge/tests-283%2F283-brightgreen)](https://github.com/flaviomoreir4/laravel-transfeera/actions/workflows/ci.yml)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon)
[![Rector](https://img.shields.io/badge/Rector-clean-brightgreen)](rector.php)
[![Pint](https://img.shields.io/badge/Pint-PSR%2012-brightgreen)](pint.json)

</div>

---

## Índice

- [Visão Geral](#visão-geral)
- [Instalação](#instalação)
- [Configuração](#configuração)
- [Uso Rápido](#uso-rápido)
- [Recursos por Domínio](#recursos-por-domínio)
  - [Pagamentos](#pagamentos)
  - [Recebimentos](#recebimentos)
  - [Pix Automático](#pix-automático)
  - [Conta Certa / Validações](#conta-certa--validações)
  - [Hub de Contas](#hub-de-contas)
  - [MED / Infrações](#med--infrações)
- [Webhooks](#webhooks)
- [Tratamento de Erros](#tratamento-de-erros)
- [Autenticação & mTLS](#autenticação--mtls)
- [Multi-tenancy (Hub de Contas)](#multi-tenancy-hub-de-contas)
- [Comandos Artisan](#comandos-artisan)
- [Testes](#testes)
- [Documentação](#documentação)
- [Contribuição](#contribuição)
- [Licença](#licença)

---

## Visão Geral

O **Laravel Transfeera SDK** é um pacote Laravel nativo que encapsula toda a API da [Transfeera](https://transfeera.com) — plataforma de pagamentos e recebimentos via Pix, boletos e transferências.

**Diferenciais:**

- 🪶 **Zero dependências externas** — usa apenas `illuminate/support`, `illuminate/http` e `illuminate/contracts`
- 🏗️ **Arquitetura por domínios** — 24 Resources organizados em 7 domínios de negócio
- 🧱 **DTOs tipados** — `readonly class` para requests e responses, sem bibliotecas externas
- 🔐 **mTLS automático** — certificação mútua TLS em produção sem configuração manual extra
- 🪝 **Webhooks completos** — validação HMAC-SHA256, eventos Laravel, controllers pré-registrados
- 🧪 **Testado em CI** — matrix PHP 8.3/8.4 × Laravel 12/13, PHPStan level 8, Rector
- 🔄 **Multi-tenancy** — suporte a múltiplas contas digitais via `accountId`

---

## Instalação

```bash
composer require flaviomoreir4/laravel-transfeera
```

O Service Provider é registrado automaticamente via auto-discovery do Laravel.

Publique a configuração (opcional):

```bash
php artisan vendor:publish --tag=transfeera-config
```

---

## Configuração

Adicione ao seu `.env`:

```env
# Ambiente: sandbox | production
TRANSFEERA_ENVIRONMENT=sandbox
TRANSFEERA_CLIENT_ID=seu_client_id
TRANSFEERA_CLIENT_SECRET=seu_client_secret
TRANSFEERA_USER_AGENT="MeuApp (email@dominio.com)"

# Opcional — mTLS (obrigatório em produção)
TRANSFEERA_MTLS_CERT_PATH=/caminho/cert.pem
TRANSFEERA_MTLS_KEY_PATH=/caminho/key.pem

# Opcional — timeout e retry
TRANSFEERA_TIMEOUT=30
TRANSFEERA_RETRY_MAX=3
TRANSFEERA_RETRY_DELAY=100

# Opcional — webhook secrets
TRANSFEERA_WEBHOOK_SECRET_PAYMENTS=secret-pagamentos
TRANSFEERA_WEBHOOK_SECRET_RECEIVABLES=secret-recebimentos
TRANSFEERA_WEBHOOK_SECRET_CONTA_CERTA=secret-conta-certa
```

Todas as chaves têm defaults seguros (sandbox, sem credenciais). O SDK valida a configuração no boot e emite warnings no log se algo estiver inconsistente.

> **⚠️ Produção:** O mTLS é **obrigatório** para as APIs de Pagamentos e Conta Certa em produção. Configure `TRANSFEERA_MTLS_CERT_PATH` e `TRANSFEERA_MTLS_KEY_PATH` apontando para seus certificados `.pem`.

---

## Uso Rápido

### Via Facade

```php
use Transfeera;

// Criar um lote de pagamentos
$batch = Transfeera::batches()->create([
    'name' => 'Pagamento fornecedores',
    'type' => 'manual',
]);

// Consultar saldo
$balance = Transfeera::statement()->getBalance();

// Validar conta bancária (Conta Certa)
$validation = Transfeera::contaCertaValidations()->validate([
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789-0',
    'document' => '123.456.789-00',
]);
```

### Via Injeção de Dependência

```php
use FlavioMoreir4\Transfeera\TransfeeraClient;

class PaymentService
{
    public function __construct(
        private TransfeeraClient $transfeera,
    ) {}

    public function payout(array $transfers): BatchResponseDTO
    {
        return $this->transfeera->batches()->create([
            'name' => 'Lote de pagamentos',
            'transfers' => $transfers,
        ]);
    }
}
```

---

## Recursos por Domínio

### Pagamentos

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `batches()` | `create()`, `list()`, `get()`, `update()`, `delete()` | `BatchResponseDTO` |
| `transfers()` | `create()`, `get()`, `update()`, `delete()` | `TransferResponseDTO` |
| `billets()` | `create()`, `get()`, `list()`, `update()`, `delete()` | `BilletResponseDTO` |
| `banks()` | `list()` | `BankResponseDTO[]` |
| `statement()` | `getBalance()`, `getTransactions()` | `StatementResponseDTO` / `array` |
| `recurrences()` | `create()`, `get()`, `list()`, `update()`, `delete()` | `RecurrenceResponseDTO` |
| `pix()` | `consultKey()`, `parseEMV()` | `PixResponseDTO` / `array` |

```php
// Criar lote com transferências
$batch = Transfeera::batches()->create([
    'name' => 'Fornecedores Julho',
    'type' => 'manual',
]);

// Adicionar transferência ao lote
$transfer = Transfeera::transfers($batch['id'])->create([
    'amount' => 150000,                     // R$ 1.500,00 (em centavos)
    'pix_key' => 'cliente@email.com',
    'pix_key_type' => 'email',
    'description' => 'Pagamento nota 123',
]);

// Consultar saldo
$balance = Transfeera::statement()->getBalance();
// ['balance' => 500000, 'blocked' => 100000, 'available' => 400000]
```

### Recebimentos

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `pixKeys()` | `create()`, `list()`, `get()`, `update()`, `delete()` | `PixKeyResponseDTO[]` |
| `pixQrCodes()` | `create()`, `list()`, `get()` | `PixQrCodeResponseDTO` |
| `pixCashIn()` | `list()`, `get()` | `PixCashInResponseDTO[]` |
| `charges()` | `create()`, `list()`, `get()`, `update()`, `delete()`, `downloadPdfByChargeId()` | `ChargeResponseDTO` |
| `paymentLinks()` | `create()`, `list()`, `get()`, `delete()` | `PaymentLinkResponseDTO` |

```php
// Criar cobrança Pix com vencimento
$charge = Transfeera::charges()->create([
    'payer_document' => '123.456.789-00',
    'payer_name' => 'João Silva',
    'amount' => 50000,                // R$ 500,00 (centavos)
    'due_date' => '2025-08-15',
    'type' => 'pix',
]);

// Baixar PDF do boleto
$pdf = Transfeera::charges()->downloadPdfByChargeId($charge->id);

// Criar chave Pix
$key = Transfeera::pixKeys()->create([
    'type' => 'email',
    'value' => 'cobranca@exemplo.com',
]);
```

### Pix Automático

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `pixAutomaticoAuthorizations()` | `create()`, `list()`, `get()`, `revoke()` | `AuthorizationResponseDTO` |
| `pixAutomaticoPaymentIntents()` | `create()`, `list()`, `get()`, `cancel()` | `PaymentIntentResponseDTO` |

```php
// Criar autorização Pix Automático
$auth = Transfeera::pixAutomaticoAuthorizations()->create([
    'payer_document' => '123.456.789-00',
    'payer_name' => 'João Silva',
    'payer_bank' => '341',
    'limit_amount' => 100000,         // R$ 1.000,00 (centavos)
    'limit_type' => 'monthly',
]);

// Criar instrução de pagamento
$intent = Transfeera::pixAutomaticoPaymentIntents()->create([
    'authorization_id' => $auth->id,
    'amount' => 50000,
    'description' => 'Assinatura mensal',
]);
```

### Conta Certa / Validações

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `contaCertaValidations()` | `validate()`, `get()`, `list()`, `listBanks()` | `ValidationResponseDTO` / `array` |
| `contaCertaBanks()` | `list()` | `BankResponseDTO[]` |

```php
// Validar conta bancária
$result = Transfeera::contaCertaValidations()->validate([
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789-0',
    'document' => '123.456.789-00',
    'account_type' => 'corrente',
]);
```

### Hub de Contas

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `accounts()` | `create()`, `list()`, `get()`, `update()`, `delete()` | `AccountResponseDTO` |

```php
// Criar conta digital
$account = Transfeera::accounts()->create([
    'name' => 'Conta Cliente A',
    'document' => '12.345.678/0001-90',
    'type' => 'company',
]);
```

### MED / Infrações

| Resource | Métodos | Response DTO |
|----------|---------|-------------|
| `infractions()` | `analyze()`, `analyzeBatch()`, `list()`, `get()`, `return()`, `returnBatch()` | `InfractionResponseDTO` / `array` |

```php
// Analisar infração individual
$analysis = Transfeera::infractions()->analyze([
    'end_to_end_id' => 'E123456789012024...',
    'infraction_type' => 'fraud',
]);

// Devolução em lote
$result = Transfeera::infractions()->returnBatch([
    'infractions' => [...],
]);
```

---

## Webhooks

O SDK expõe 3 endpoints para receber notificações da Transfeera:

| Rota | Domínio | Controller |
|------|---------|------------|
| `POST /webhooks/transfeera/payments` | Pagamentos | `WebhookController@payments` |
| `POST /webhooks/transfeera/receivables` | Recebimentos | `WebhookController@receivables` |
| `POST /webhooks/transfeera/conta-certa` | Conta Certa | `WebhookController@contaCerta` |

**Validação de assinatura:** HMAC-SHA256, automática. Configure os secrets no `.env`.

```php
// Ouvir eventos no EventServiceProvider
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;

protected $listen = [
    TransfeeraWebhookReceived::class => [
        MinhaListener::class,
    ],
];
```

Publicar rotas (opcional):
```bash
php artisan vendor:publish --tag=transfeera-routes
```

> 📖 Consulte [docs/webhooks.md](docs/webhooks.md) para detalhes completos.

---

## Tratamento de Erros

Todas as exceptions estendem `TransfeeraException`:

```php
use FlavioMoreir4\Transfeera\Exceptions\{
    TransfeeraException,
    TransfeeraAuthenticationException,  // 401
    TransfeeraValidationException,      // 422 — use $e->getErrors()
    TransfeeraRateLimitException,       // 429 — use $e->getRetryAfter()
    PaymentException,                   // Erros em Pagamentos
    ReceivableException,                // Erros em Recebimentos
    PixAutomaticoException,             // Erros em Pix Automático
    ContaCertaException,                // Erros em Conta Certa
    AccountException,                   // Erros no Hub de Contas
    InfractionException,                // Erros em MED/Infrações
};

try {
    $batch = Transfeera::batches()->create([...]);
} catch (TransfeeraValidationException $e) {
    // Campos inválidos
    foreach ($e->getErrors() as $field => $messages) { ... }
} catch (TransfeeraRateLimitException $e) {
    // Rate limit — backoff
    $retryAfter = $e->getRetryAfter();
    $limit = $e->getLimit();
    $remaining = $e->getRemaining();
} catch (PaymentException $e) {
    // Erro específico de pagamentos
}
```

> 📖 Consulte [docs/exceptions.md](docs/exceptions.md) para a hierarquia completa.

---

## Autenticação & mTLS

O SDK gerencia o ciclo de vida do token OAuth2 `client_credentials` automaticamente:

- **Cache** — token armazenado no cache do Laravel (store configurável)
- **Renovação antecipada** — renova 60s antes do `expires_in` real
- **Concorrência** — lock de cache evita múltiplas renovações simultâneas
- **Multi-tenancy** — tokens separados por `accountId`

```php
// Forçar renovação manual
Transfeera::getConfig(); // ou via TokenManager
```

**mTLS** em produção é aplicado automaticamente nas APIs de Pagamentos e Conta Certa. Configure:

```env
TRANSFEERA_MTLS_CERT_PATH=/etc/ssl/transfeera/cert.pem
TRANSFEERA_MTLS_KEY_PATH=/etc/ssl/transfeera/key.pem
```

---

## Multi-tenancy (Hub de Contas)

Todos os Resources aceitam `$accountId` opcional:

```php
// Operar como conta específica
$batches = Transfeera::batches('acc_123')->list();

// Criar recurso em nome de outra conta
$batch = Transfeera::batches('acc_456')->create([
    'name' => 'Lote Conta B',
]);
```

O `TokenManager` adiciona `scope=account_id:{accountId}` ao token, garantindo escopo correto.

---

## Comandos Artisan

| Comando | Descrição |
|---------|-----------|
| `php artisan transfeera:install` | Publica configuração e exibe instruções |
| `php artisan transfeera:check` | Verifica conectividade, credenciais e mTLS |

```bash
php artisan transfeera:check
# 🔍 Verificando Transfeera SDK...
# 📋 Ambiente: sandbox
# ✅ Ambiente válido.
# ✅ Credenciais configuradas.
# ✅ Endpoint de autenticação acessível.
```

---

## Testes

```bash
composer test              # Pest (283 testes, 482 asserções)
composer test-coverage     # Com cobertura (PHP 8.3+)
composer phpstan           # PHPStan level 8
composer rector            # Rector dry-run
composer format            # Pint PSR-12
```

O CI roda em matrix PHP 8.3/8.4 × Laravel 12/13.

---

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| [Pagamentos](docs/pagamentos.md) | Lotes, transferências, boletos, saldo, recorrências |
| [Recebimentos](docs/recebimentos.md) | Chaves Pix, QR Codes, Cash-in, cobranças, links |
| [Pix Automático](docs/pix-automatico.md) | Autorizações, Payment Intents, fluxo completo |
| [Conta Certa](docs/conta-certa.md) | Validações, bancos suportados |
| [Hub de Contas](docs/hub-contas.md) | Contas digitais, onboarding, tenancy |
| [MED / Infrações](docs/med.md) | Infrações, análise individual/lote, devolução |
| [Webhooks](docs/webhooks.md) | Rotas, secrets, validação HMAC, listeners |
| [Exceptions](docs/exceptions.md) | Hierarquia completa, catch, métodos úteis |
| [Middlewares](docs/middlewares.md) | Config, logging, métricas, Prometheus |
| [Erros](docs/erros.md) | Códigos HTTP, handlers, retry |
| [Primeiro Pagamento](docs/primeiro-pagamento.md) | Passo a passo inicial |
| [Primeiro Recebimento](docs/primeiro-recebimento.md) | Passo a passo inicial |
| [Changelog](docs/changelog.md) | Histórico de versões (Keep a Changelog) |
| [Roadmap](docs/roadmap.md) | Planejamento de versões futuras |

**Links oficiais da Transfeera:**
- [Documentação da API](https://docs.transfeera.dev/reference/endpoints)
- [Índice completo (llms.txt)](https://docs.transfeera.dev/llms.txt)

---

## Contribuição

Veja [CONTRIBUTING.md](CONTRIBUTING.md) para detalhes.

```
composer test && composer phpstan && composer rector
```

---

## Licença

MIT © [Flávio Moreira](LICENSE). Veja o arquivo [LICENSE](LICENSE) para detalhes.
