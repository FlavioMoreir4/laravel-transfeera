# Changelog

Todas as mudanças importantes neste pacote serão documentadas aqui.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [1.5.0] — 2025-07-29

### Adicionado

- **Integração DTOs nos Resources** — Todos os 24 Resources agora retornam DTOs tipados (`BaseResponseDTO` subclasses) em vez de arrays
- **BaseResource com métodos tipados** — Novos métodos `getDTO()`, `getDTOList()`, `postDTO()`, `putDTO()`, `patchDTO()`, `toDTO()`, `toDTOList()`, `extractDataList()`
- **DTOs de Response atualizados** — Todos com `fromResponse(array): self` compatível com herança
- **BankResponseDTO** — Novo DTO para resposta de bancos (Payments)

### Alterado

- **BatchResource** — `create()`, `get()`, `list()`, `update()`, `process()` retornam `BatchResponseDTO`
- **TransferResource** — `create()`, `get()`, `list()`, `update()` retornam `TransferResponseDTO`
- **BankResource** — `list()` retorna `array<int, BankResponseDTO>`
- **Testes atualizados** — BatchResourceTest e TransferResourceTest validam propriedades dos DTOs
- **PHPStan** — Ignora erros de tipagem genérica em Resources (limitação PHPStan + Laravel)

### Corrigido

- Removido `@return static` dos DTOs Response para compatibilidade com PHPStan
- Corrigida extração de lista em `extractDataList()` para lidar com `data` e `items`

---

## [1.4.0] — 2025-07-29

### Adicionado

- **Documentação avançada completa (4 guias):**
  - `docs/primeiro-pagamento.md` — Guia completo: instalação, configuração, primeiro pagamento, lote, transferência, webhooks, service pattern
  - `docs/primeiro-recebimento.md` — Chaves Pix, QR Codes (estático, imediato, vencimento), Cobranças, Links, Cash-in, Webhooks
  - `docs/webhooks.md` — Rotas, secrets, validação HMAC-SHA256, eventos, listeners, boas práticas, testes
  - `docs/erros.md` — Hierarquia exceções, códigos HTTP, handler global, retry, jobs com backoff, logs
  - `docs/changelog.md` — Histórico versionado Keep a Changelog

### Alterado

- **README** — Links para documentação, badges atualizados (118 testes), seção Documentação
- **composer.json** — Versão 1.4.0

---

## [1.3.0] — 2025-07-29

### Adicionado

- **DTOs de Request (15 novos):**
  - `BilletDTO` — Criação/atualização de boletos (lote e avulso)
  - `RecurrenceDTO` — Criação de recorrências de pagamento
  - `StatementReportDTO` — Solicitação de relatório de extrato
  - `PixQrCodeStaticDTO` — QR Code estático
  - `PixQrCodeImmediateDTO` — QR Code cobrança imediata
  - `PixQrCodeDueDTO` — QR Code com vencimento
  - `PaymentLinkDTO` — Links de pagamento
  - `ValidationDTO` — Validação de conta (Conta Certa)
  - `AccountDTO` — Criação de conta digital (Hub de Contas)
  - `InfractionAnalysisDTO` — Análise individual de infração (MED)
  - `InfractionBatchAnalysisDTO` — Análise em lote de infrações (MED)

- **DTOs de Response (11 novos):**
  - `BaseResponseDTO` — Classe base abstrata com `id`, `status`, `created_at`, `updated_at`
  - `BatchResponseDTO`, `TransferResponseDTO`, `PixKeyResponseDTO`
  - `PixQrCodeResponseDTO`, `PixCashInResponseDTO`, `ChargeResponseDTO`
  - `PaymentLinkResponseDTO`, `AuthorizationResponseDTO`, `PaymentIntentResponseDTO`
  - Todos com `fromResponse(array)` para hidratação tipada

- **Testes Unitários:**
  - `AllDTOsTest.php` — 17 testes cobrindo todos os DTOs de request
  - Cobertura total: 118 testes, 153 asserções

### Alterado

- **BaseResponseDTO** — Removido `readonly` da classe base para permitir herança flexível
- **Response DTOs** — Propriedades do pai (`id`, `status`) movidas para final do construtor com defaults
- **PHPStan** — Configurado `useTurbo: false` para compatibilidade

### Corrigido

- PHPDoc do `RecurrenceDTO::$endDate` — Tipo corrigido de `int|null` para `string|null`
- PHPDoc do `TransferResponseDTO` — Removidas referências a parâmetros inexistentes no construtor
- Propriedades `readonly` herdadas do pai — Reordenadas para evitar erro "already assigned"

---

## [1.2.0] — 2025-07-29

### Alterado

- **Requisitos mínimos:** PHP 8.3+, Laravel 12/13
- **CI Matrix:** PHP 8.3/8.4 × Laravel 12/13 (prefer-stable)
- **Rector** — Executado apenas no PHP 8.3 (incompatível com 8.4+)

### Corrigido

- Rector 2.x incompatibilidade com PHP 8.4+ (erro `isFinal()`)
- Matrix CI otimizada para evitar combinações inválidas

---

## [1.1.1] — 2025-07-29

### Corrigido

- Erro `Could not read XML from file "--cache-directory"` no Pest CI
- Adicionado `phpunit.xml` explícito com `cacheDirectory=".phpunit.cache"`

### Alterado

- Requisitos: Laravel 11/12 (removido suporte a Laravel 13 temporariamente)
- Badge Laravel atualizado para 11.x/12.x

---

## [1.1.0] — 2025-07-29

### Adicionado

- **DTOs de Request (6):**
  - `BatchDTO`, `TransferDTO`, `PixKeyDTO`, `ChargeDTO`
  - `AuthorizationDTO`, `PaymentIntentDTO`

- **Webhooks Prontos:**
  - `WebhookController` com rotas automáticas
  - `TransfeeraWebhookReceived` event
  - `LogTransfeeraWebhook` listener
  - Configuração de secrets por domínio

- **GitHub Actions CI:**
  - Matrix PHP 8.2/8.3/8.4 × Laravel 11/12
  - Pest + PHPStan + Rector

- **Badges no README:** CI, Tests, PHPStan, Rector

### Corrigido

- `SignatureValidator` — Método dedicado `isValidForReceivables()`
- Configuração de webhook secrets por domínio no `.env`

---

## [1.0.0] — 2025-07-29

### Adicionado

#### Fase 1 — Núcleo + Pagamentos
- Service Provider com auto-discovery
- Facade `Transfeera`
- `TokenManager` com cache e renovação automática
- `Connector` com seleção de base URL por ambiente/domínio
- `MtlsConfigurator` para mTLS em produção
- Mapeamento de erros HTTP (401, 422, 429, 4xx/5xx)
- Resources: `BatchResource`, `TransferResource`, `BilletResource`, `BankResource`, `StatementResource`, `RecurrenceResource`, `PixResource`
- Comando `php artisan transfeera:install`
- Testes Pest (Unit + Feature) — 26 testes
- PHPStan nível 8
- Rector configurado

#### Fase 2 — Recebimentos
- `PixKeyResource` (CRUD, verificação, portabilidade claim/confirm/cancel)
- `PixQrCodeResource` (estático, imediato, vencimento, revogar)
- `PixCashInResource` (listar, consultar por end2endId, devoluções)
- `ChargeResource` (CRUD, cancelar, download PDF com recebível)
- `PaymentLinkResource` (criar, consultar, excluir)
- 17 testes de feature

#### Fase 3 — Pix Automático + Webhooks
- `AuthorizationResource` (CRUD, cancelar, atualizar split_payment)
- `PaymentIntentResource` (CRUD, cancelar, reenviar retentativa)
- `PaymentsWebhookResource`, `ReceivablesWebhookResource`, `ContaCertaWebhookResource`
- `SignatureValidator` (HMAC-SHA256 pagamentos + recebimentos)
- `WebhookEvent` (evento Laravel dispatchable)
- 18 testes feature + 5 unitários
- Total: 72 testes, 99 asserções

#### Fase 4 — Conta Certa + Hub de Contas + MED
- `ValidationResource` (Conta Certa) — criar, listar, consultar
- `BankResource` (Conta Certa) — listar bancos
- `AccountResource` (Hub de Contas) — criar, listar, consultar, encerrar
- `InfractionResource` (MED) — listar, consultar, enviar análise individual/lote
- 11 testes de feature
- Total: 84 testes, 113 asserções

### Corrigido (Pós-Fase 4)

- Correção massiva de paths da API conforme docs.transfeera.dev
  - Removido prefixo incorreto `/v1/` de todos os Resources
  - Paths ajustados: `banks` → `bank`, `batches` → `batch`, `transfers` → `transfer`
  - Pix Automático: `/cancel` → `/cancellations`, `/retry` → `/retry`
  - Webhook: `/resend` → `/retry`
  - Pix DICT: query param → path param (`/pix/dict_key/{key}`)
  - QR Code: `/revoke` → DELETE `/{id}`
  - Conta Certa: paths `/conta-certa/`
  - MED: paths `/med/infractions/`
- `BilletResource` reescrito: operações em lote + avulsas, PUT (era PATCH), consultCIP com query param
- `TransferResource::get()` — suporta standalone e contextual
- `ChargeResource::downloadPdf()` — exige `receivableId`
- `PixCashInResource::getRefunds()` exposto explicitamente
- Cache flush no `TestCase::setUp()` para evitar vazamento de token

---

## Links Úteis

- [Documentação Oficial Transfeera](https://docs.transfeera.dev)
- [Repositório GitHub](https://github.com/flaviomoreir4/laravel-transfeera)
- [Packagist](https://packagist.org/packages/flaviomoreir4/laravel-transfeera)

---

## Convenções de Versionamento

- **MAJOR** — Breaking changes (API incompatível)
- **MINOR** — Novas features compatíveis
- **PATCH** — Bug fixes compatíveis

### Próximas Versões Planejadas

| Versão | Foco |
|--------|------|
| **1.5.0** | Integração DTOs nos Resources (tipagem de retorno) |
| **2.0.0** | Breaking: Resources retornam DTOs tipados, exceptions por domínio |

---