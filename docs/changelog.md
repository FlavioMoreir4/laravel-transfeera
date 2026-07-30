# Changelog

Todas as mudanças importantes neste pacote serão documentadas aqui.

O formato é baseado em [Keep a Changelog](https://keepachangelog.com/pt-BR/1.1.0/),
e este projeto adere ao [Versionamento Semântico](https://semver.org/lang/pt-BR/).

---

## [Unreleased]

## [1.12.0] — 2025-07-30

### Added

- **`PaymentLinkResource::list()`** — novo método para consultar todos os links de pagamento com filtros (`status`, `name`, paginação). Retorna `array<int, PaymentLinkResponseDTO>`.
- **Testes do `PaymentLinkResource`** — 4 testes (create, list, get, delete) totalizando 291 asserções.

### Full Changelog

203 testes, 291 asserções — PHPStan level 8 (0 erros), Rector clean, Pint PSR-12.

---

## [1.10.0] — 2025-07-30

### Added

- **7 novos Response DTOs**: `BilletResponseDTO`, `StatementResponseDTO`,
  `PixResponseDTO`, `AccountResponseDTO`, `InfractionResponseDTO`,
  `ValidationResponseDTO`, `RecurrenceResponseDTO` — tipagem completa para
  todos os Resources que ainda retornavam array puro.
- **15 Resources migrados para DTOs**: `BilletResource`, `StatementResource`,
  `PixResource`, `AccountResource`, `InfractionResource`, `ValidationResource`,
  `RecurrenceResource`, `PixKeyResource`, `PixQrCodeResource`,
  `PixCashInResource`, `ChargeResource`, `PaymentLinkResource`,
  `AuthorizationResource`, `PaymentIntentResource`, `ContaCerta\BankResource`
  — agora retornam DTOs tipados via `getDTO`/`postDTO`/`getDTOList`.
- **Infraestrutura de testes de integração**: `IntegrationTestCase` base com
  skip automático se não houver `.env` + `ExampleTest` com exemplos reais.
- **Guia de migração Laravel 13**: `docs/laravel-13.md` com matriz de
  compatibilidade, requisitos, procedimento de upgrade.

### Changed

- **Estratégia de versionamento revisada**: v2.0.0 não está mais cancelado.
  Adotada **estratégia híbrida** — v1.x contínua com deprecação lenta, v2.0.0
  planejada para ~2026 Q1. `@deprecated` introduzido em v1.10+ com ~6 meses
  de aviso antes da remoção em v2.0. Ver ADR-006 e UPGRADE.md para detalhes.
- **`@deprecated` adicionado** ao método `BaseResource::deleteRaw()` —
  será removido na v2.0.0.
- **Documentação reescrita**: README.md, REQUISITOS.md, ADRs (10),
  UPGRADE.md — todos reestruturados com modelos de referência do ecossistema
  Laravel.
- **14 testes atualizados** para usar propriedades de DTOs (`$response->id`)
  em vez de array access (`$response['id']`).

### Documentation

- `docs/laravel-13.md`: guia de migração e compatibilidade Laravel 13
- `docs/erros.md`: seção "Estratégia de Retry para Rate Limit" (backoff
  exponencial, Laravel Queue com backoff dinâmico)
- Roadmap atualizado — marcos M8-M11 concluídos

---

## [1.9.0] — 2025-07-30

### Adicionado

- **GitHub Release workflow** — criação automática de release ao push de tag (`v*`)
- **Comando `transfeera:check`** — health check de conectividade com a API (credenciais, mTLS, endpoint auth)
- **Rate limit headers nas exceptions** — `TransfeeraRateLimitException::getRetryAfter()`, `getLimit()`, `getRemaining()`, `getReset()` populados a partir dos headers `Retry-After`, `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`
- **Coverage no CI** — `composer test-coverage --min=90` na matrix PHP 8.3
- **Validação de config no boot** — `TransfeeraServiceProvider` emite warnings de log se config estiver incompleta (ambiente inválido, credenciais faltando, mTLS ausente em produção)
- **Testes de ServiceProvider (11)** — verifica registros de singletons, alias e resolução de todos os bindings
- **Testes de InstallCommand (4)** — verifica registro dos comandos artisan e execução

### Alterado

- **README badges** — adicionado badge Pint PSR-12, atualizado contagem de testes (183→199)
- **Pint** — `composer format` agora executa automaticamente (1 fix aplicado)

---

## [1.8.1] — 2025-07-30

### Adicionado

- **Novos testes (14)** — cobertura de `TransferResource::update()`, `PixQrCodeResource::get()`, webhooks completos para receivables e conta_certa (12 testes)
- **Pint config** — `pint.json` com preset Laravel e script `composer format`
- **Script `composer analyse`** — alias para `composer phpstan`
- **`.env.example`** — template completo de variáveis de ambiente

### Corrigido

- **AGENTS.md** — versão atualizada para v1.8.0, contagem de testes (169), Pest versão 5
- **CONTRIBUTING.md** — Pest 5 em vez de Pest 4
- **Formatação PSR-12** — 113 arquivos corrigidos automaticamente via Pint (75 issues)

---

## [1.8.0] — 2025-07-30

### Adicionado

- **Testes de error handling no Connector (13 testes)** — Retry transiente (recuperação após falha), exaustão de tentativas, recuperação após 429 e 422, `ConnectionException` em timeout e falha de DNS, timeout transiente com recuperação
- **Mapeamento completo de erros HTTP → exceptions tipadas (6 testes)** — Payload reais para payments, receivables, pix_automatico, conta_certa, accounts, infractions mais 401/422/429
- **Testes dos middlewares (8 testes)** — LoggingMiddleware (log ativo, nível warning em 500+, sanitização de headers, contexto com status/duration) e MetricsMiddleware (prefixo configurável, não interfere em sucesso/erro, executa em exceção)
- **Cobertura de Resources parciais (13 testes novos)** — `PixResource` (lookupKey, parseEmv, validações), `RecurrenceResource` (list, listPayments, cancel), `StatementResource` (balance, withdraw, requestReport, getReport), `PixKeyResource` (resendVerificationCode)
- **Testes de concorrência do token (5 testes)** — Lock evita renovação concorrente, lock retorna token válido após espera, cache miss duplo gera apenas uma renovação, cache isolado por accountId
- **Testes de mTLS condicional por domínio (2 testes)** — Ativa em produção para payments e conta_certa; pula em sandbox mesmo para domínios que exigem

### Alterado

- **Connector** — `retry()` agora usa `throw: false` para compatibilidade com Laravel 13+
- **Middlewares refatorados** — Migrados de Guzzle HandlerStack (`withMiddleware()`) para chamadas diretas no `finally` do `Connector::execute()`, compatível com Laravel 13+ que mudou a assinatura do `withMiddleware()`
- **LoggingMiddleware** — Parâmetro `$response` agora nullable; proteção contra `$response` nulo no `finally`
- **MetricsMiddleware** — Simplificado para método `recordMetric()` chamado diretamente
- **MtlsConfiguratorTest** — Adicionados testes de ativação por domínio específico

---

## [1.7.0] — 2025-07-29

### Adicionado

- **Exceptions tipadas por domínio** — Nova hierarquia de exceptions para melhor debugging e handling:
  - `PaymentException` — API de Pagamentos (lotes, transferências, boletos, bancos, saldo/extrato, Pix, recorrências)
  - `ReceivableException` — API de Recebimentos (chaves Pix, QR Codes, cash-in, cobranças, links)
  - `PixAutomaticoException` — API de Pix Automático (autorizações, payment intents)
  - `ContaCertaException` — API de Conta Certa / Validações
  - `AccountException` — API de Hub de Contas
  - `InfractionException` — API de Infrações MED
  - Todas herdam de `TransfeeraException` e possuem método estático `fromResponse()`

### Alterado

- **Connector** — Mapeamento de erros HTTP para exceptions tipadas por domínio:
  - 401/422/429 → exceptions base (AuthenticationException, ValidationException, RateLimitException)
  - Outros códigos → exceptions específicas do domínio (PaymentException, ReceivableException, etc.)
- **Resources** — Lançam exceptions tipadas automaticamente via Connector

---

## [1.6.0] — 2025-07-29

### Adicionado

- **Middleware de Logging** — `LoggingMiddleware` com log de requisições/respostas, headers sensíveis sanitizados, truncamento de body, níveis configuráveis
- **Middleware de Métricas** — `MetricsMiddleware` com contadores de requisições, histogramas de latência, taxa de erro, integração pronta para Prometheus/StatsD
- **Configuração via transfeera.php** — Seções `logging` e `metrics` com opções habilitadas/desabilitadas
- **Integração no Connector** — Middlewares aplicados automaticamente quando habilitados na configuração

### Alterado

- **Connector** — Refatorado para executar via método `execute()` genérico, suportando GET/POST/PUT/PATCH/DELETE
- **ServiceProvider** — Registra middlewares como singletons e injeta no Connector

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
| **1.7.0** | Exceptions tipadas por domínio, testes de integração |
| **1.8.0** ✅ | Cobertura de testes: error handling, middlewares, concorrência, mTLS, Resources parciais |
| **1.8.1** ✅ | PATCH: Pint, scripts, 14 novos testes webhook/transfer/qrcode, docs |
| **1.9.0** ✅ | Release workflow, transfeera:check, rate limit headers, coverage CI, config validation, ServiceProvider/InstallCommand tests |
| **1.10.0** ✅ | Response DTOs, integração Laravel 13, testes integração, rate limit docs |
| **1.11.0+** | MINORs — compatibilidade garantida, deprecação lenta |
| **2.0.0** 🎯 | ~2026 Q1 — breaking com 6+ meses de aviso (ADR-006) |

Consulte [docs/roadmap.md](docs/roadmap.md) para o plano completo.

---