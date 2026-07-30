# Roadmap — laravel-transfeera

> Filosofia**: Estratégia híbrida — v1.x contínua com deprecação lenta, v2.0.0 planejada ~2026 Q1.
> Apenas **MINORs** (features compatíveis) e **PATCHs** (correções) até lá.
> Atual — **v1.17.0** (262 testes, 426 asserções, PHPStan 0, Rector clean, Pint PSR-12).

---

## Diagnóstico Atual — Cobertura

### ✅ Sólido
- 24 Resources, 7 domínios, cobertura total de endpoints
- 201 testes, 283 asserções — zero failures
- PHPStan level 8, Rector clean
- CI matrix: PHP 8.3/8.4 × Laravel 12/13
- 12 docs, ServiceProvider, Facade, comandos artisan
- Token com cache/lock, mTLS condicional, middlewares
- Webhooks com HMAC-SHA256

### 🔍 Lacunas Encontradas

| # | Área | Detalhe | Tipo |
|---|------|---------|------|
| L1 | **Testes** | TransferResource: `update()` não testado | PATCH |
| L2 | **Testes** | PixQrCodeResource: `get()` não testado | PATCH |
| L3 | **Testes** | ChargeResource: `downloadPdfByChargeId()` não testado | PATCH |
| L4 | **Testes** | WebhookResourceTest: cobre só payments; faltam receivables e conta_certa individualmente | PATCH |
| L5 | **Scripts** | `composer format` e `composer analyse` referenciados mas inexistentes | PATCH |
| L6 | **Config** | `composer format` (Pint) — sem config, sem script | PATCH |
| L7 | **Docs** | AGENTS.md desatualizado (v1.7.0, 118 testes) | PATCH |
| L8 | **Docs** | CONTRIBUTING.md diz "Pest 4" (atual: Pest 5) | PATCH |
| L9 | **Infra** | `.env.example` referenciado mas não existe | PATCH |
| L10 | **Testes** | ServiceProvider — sem teste de registro de bindings | MINOR |
| L11 | **Testes** | InstallCommand — sem teste (comando artisan) | MINOR |
| L12 | **CI** | Coverage report não é gerado (pcov instalado mas não usado) | MINOR |
| L13 | **CI** | GitHub Release automática (tag → release) — não existe | MINOR |
| L14 | **Feature** | Comando `transfeera:check` — health check das credenciais | MINOR |
| L15 | **Feature** | Rate limit headers expostos nas exceptions | MINOR |
| L16 | **Feature** | Validação de config no boot do ServiceProvider | MINOR |
| L17 | **Feature** | Resources que retornam array puro poderiam ter DTOs de Response | MINOR |
| L18 | **Testes** | Testes de integração opcionais (sandbox real) | MINOR |

---

## Plano de Entregas

### ♻️ PATCH 1.8.1 — Correções Rápidas

> Esforço: ~1-2h. 9 itens pequenos, sem dependência entre si.

| # | Tarefa | Arquivos | Esforço |
|---|--------|----------|---------|
| P1 | `TransferResource::update()` — adicionar teste | `tests/Feature/TransferResourceTest.php` | 🔵 15min |
| P2 | `PixQrCodeResource::get()` — adicionar teste | `tests/Feature/PixQrCodeResourceTest.php` | 🔵 15min |
| P3 | `ChargeResource::downloadPdfByChargeId()` — adicionar teste | `tests/Feature/ChargeResourceTest.php` | 🔵 15min |
| P4 | WebhookResourceTest — tests para receivables e conta_certa | `tests/Feature/WebhookResourceTest.php` | 🔵 30min |
| P5 | `composer format` — adicionar script Pint + config `pint.json` | `composer.json`, `pint.json` | 🔵 15min |
| P6 | `composer analyse` — alias ou script correto | `composer.json` | 🔵 5min |
| P7 | AGENTS.md — atualizar versão, testes, scripts | `AGENTS.md` | 🟢 10min |
| P8 | CONTRIBUTING.md — Pest 5 em vez de Pest 4 | `CONTRIBUTING.md` | 🟢 5min |
| P9 | Criar `.env.example` | `.env.example` | 🟢 5min |

**Resultado**: ~173 testes, 250+ asserções. CI com format step.

---

### 🚀 MINOR 1.9.0 — Qualidade + Features Leves

> Esforço: ~1-2 dias. Itens que agregam valor sem breaking.

| # | Tarefa | Arquivos | Esforço | Prioridade |
|---|--------|----------|---------|------------|
| M1 | **GitHub Release workflow** ✅ | `.github/workflows/release.yml` | 🟡 1h | 🔴 Alta |
| M2 | **Coverage report no CI** ✅ | `.github/workflows/ci.yml` | 🟡 30min | 🔴 Alta |
| M3 | **ServiceProvider test** ✅ (11 tests) | `tests/Unit/ServiceProviderTest.php` | 🟡 1h | 🟡 Média |
| M4 | **InstallCommand test** ✅ (4 tests) | `tests/Unit/InstallCommandTest.php` | 🟡 1h | 🟡 Média |
| M5 | **Config validation** ✅ | `src/TransfeeraServiceProvider.php` | 🟡 30min | 🟡 Média |
| M6 | **Comando `transfeera:check`** ✅ | `src/Console/Commands/CheckCommand.php` | 🟡 1h | 🟡 Média |
| M7 | **Rate limit headers** ✅ | `src/Http/Connector.php`, exceptions | 🟡 30min | 🟢 Baixa |

**Resultado**: 199 testes, 283 asserções. Release automática. Coverage badge. transfeera:check. Rate limit headers.

---

### ✅ 1.10.0 — Response DTOs + Integração

> Concluído na v1.10.0. 201 testes, 283 asserções.

| # | Tarefa | Status |
|---|--------|--------|
| M8 | **Response DTOs** — 7 novos DTOs criados, 15 Resources integrados, 14 testes atualizados | ✅ |
| M9 | **Testes de integração** — `IntegrationTestCase` com skip condicional + `ExampleTest` | ✅ |
| M10 | **Laravel 13 migration guide** — `docs/laravel-13.md` com matriz de compatibilidade | ✅ |
| M11 | **Rate limit retry docs** — seção completa em `docs/erros.md` (backoff exponencial, Queue) | ✅ |

---

### 📋 Versões Futuras (Backlog)

| Versão | Foco | Itens |
|--------|------|-------|
| **1.10.0** | Qualidade + DX | Response DTOs, testes integração, Laravel 13 migration guide |
| **1.11.0** | Patches Documentação | AGENTS, README, REQUISITOS, .env.example, ServiceProviderTest |
|| **1.12.0** | Endpoints Faltantes | `PaymentLinkResource::list()` + testes |
|| **1.13.0** ✅ | Debug & Observabilidade | `TransfeeraRequestComplete` event, `transfeera:debug`, cookbook, domínios corrigidos |
|| **1.14.0** ✅ | Cookbook expandido | LoggingMiddleware avançado, OpenTelemetry, Prometheus, Grafana, 31 novos testes |
|| **1.15.0** ✅ | Performance & DX | RateLimitMonitor, cache-warm, TransfeeraBaseJob, docs/fila.md, 25 novos testes |
|| **1.16.0** ✅ | Qualidade & Infra | Webhook DTOs, .env.example, CONTRIBUTING.md, GitHub Release, +3 testes |
|| **1.17.0** ✅ | Cookbook + Statement DTOs | 3 receitas, StatementWithdraw/Report DTOs, Boas Práticas |
|| **1.18.0** 🎯 | **DTOs everywhere — 0 arrays** | OperationResponseDTO, PixQrCode/Authoriz/PaymentIntent/Charge/Account DTOs, PixRefund, BilletCip, PixoEmv, RecurrencePayment DTOs, +35 métodos tipados, docs pix-automatico, docs transacoes, Payments\BankResource test |
|| **2.0.0** 🎯 ~2026 Q1 | **Breaking planejado** | Drop Laravel 12/PHP 8.3, remove `*Raw()`, Connector interno, API unificada (DTOs everywhere), UPGRADE.md completo |

---

## Ordem de Implementação Recomendada

```
FASE 1 — PATCH 1.8.1 (hoje, ~2h)
  ├── P1-P4: Testes faltantes (cobertura)
  ├── P5-P6: Scripts (format + analyse)
  ├── P7-P8: Docs (AGENTS + CONTRIBUTING)
  └── P9: .env.example

FASE 2 — MINOR 1.9.0 (próxima sprint, ~2 dias)
  ├── M1: Release workflow
  ├── M2: Coverage badge
  ├── M3-M4: Testes de ServiceProvider + InstallCommand
  ├── M5: Config validation
  ├── M6: transfeera:check
  └── M7: Rate limit headers

FASE 3 — MINOR 1.10.0 (próxima sprint, ~2 dias)
  ├── M8: Response DTOs
  ├── M9: Integration tests skeleton
  ├── M10: Laravel 13 migration guide
  └── M11: Rate limit docs

FASE 4 — MINOR 1.11.0 (patches docs + qualidade)
  ├── AGENTS.md, README, REQUISITOS, .env.example
  ├── ServiceProviderTest (singleton)
  └── Badges atualizados

FASE 5 — MINOR 1.12.0 (endpoint faltante)
  ├── PaymentLinkResource::list()
  └── Testes do PaymentLinkResource

FASE 6 — MINOR 1.13.0 (debug + observabilidade)
  ├── Connector: constantes de domínio
  ├── Evento TransfeeraRequestComplete
  ├── Comando transfeera:debug
  ├── docs/cookbook.md (10 receitas, 7 domínios)
  └── PHPStan --memory-limit=512M

FASE 6+ — Backlog
  ├── 1.14.0: Cookbook expandido + logging avançado
  └── 2.0.0: Breaking planejado ~2026 Q1
```

## Notas Técnicas

- **Pint**: `composer require --dev laravel/pint` e criar `pint.json` com preset `laravel`
- **Coverage**: CI já instala pcov; basta `pest --coverage --min=90` e `coverage: pcov` no setup-php
- **Release workflow**: `softprops/action-gh-release@v2` com `generate_release_notes: true`
- **transfeera:check**: POST /authorization com dry-run (sem credenciais válidas não dá, mas validar reachability)
- **Config validation**: `ServiceProvider::boot()` → validar estrutura da config, emitir warning
