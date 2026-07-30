# Roadmap — laravel-transfeera

> **Filosofia**: Compatibilidade garantida para sempre (v2 cancelado).
> Apenas **MINORs** (features compatíveis) e **PATCHs** (correções).
> Atual — v1.8.0 (169 testes, 242 asserções, PHPStan 0, Rector clean).

---

## Diagnóstico Atual — Cobertura

### ✅ Sólido
- 24 Resources, 7 domínios, cobertura total de endpoints
- 169 testes, 242 asserções — zero failures
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
| M1 | **GitHub Release workflow** — CI auto-cria release ao push de tag | `.github/workflows/release.yml` | 🟡 1h | 🔴 Alta |
| M2 | **Coverage report no CI** — `pest --coverage` + upload artifact/badge | `.github/workflows/ci.yml`, `phpunit.xml` | 🟡 30min | 🔴 Alta |
| M3 | **ServiceProvider test** — bindings registrados corretamente | `tests/Unit/ServiceProviderTest.php` | 🟡 1h | 🟡 Média |
| M4 | **InstallCommand test** — comando artisan | `tests/Unit/InstallCommandTest.php` | 🟡 1h | 🟡 Média |
| M5 | **Config validation** — validar config no boot do ServiceProvider | `src/TransfeeraServiceProvider.php` | 🟡 30min | 🟡 Média |
| M6 | **Comando `transfeera:check`** — health check (ping credenciais + mTLS) | `src/Console/Commands/CheckCommand.php` | 🟡 1h | 🟡 Média |
| M7 | **Rate limit headers** — expor `X-RateLimit-*` nas exceptions | `src/Http/Connector.php`, exceptions | 🟡 30min | 🟢 Baixa |

**Resultado**: ~175 testes, 260+ asserções. Release automática. Badge de coverage.

---

### 🚀 MINOR 1.10.0 — Response DTOs + Integração

> Esforço: ~2-3 dias. Foco em tipagem completa e DX.

| # | Tarefa | Arquivos | Esforço | Prioridade |
|---|--------|----------|---------|------------|
| M8 | **Response DTOs para Resources que ainda retornam array** — verificar BilletResponseDTO, StatementResponseDTO, etc. | `src/DTOs/Response/`, Resources | 🟡 2h | 🟡 Média |
| M9 | **Testes de integração opcionais** — esqueleto com skip se sem credenciais | `tests/Integration/`, `.env.example` | 🟠 4h | 🟢 Baixa |
| M10 | **Laravel 13 migration guide** — documentar mudança dos middlewares | `UPGRADE.md` | 🟢 15min | 🟢 Baixa |
| M11 | **Rate limit retry documentation** — documentar comportamento atual | `docs/erros.md` | 🟢 15min | 🟢 Baixa |

**Resultado**: ~178 testes, 270+ asserções. Tipagem completa.

---

### 📋 Versões Futuras (Backlog)

| Versão | Foco | Itens |
|--------|------|-------|
| **1.11.0** | Ferramentas de debug | Comando `transfeera:debug` (log raw requests), CLI de teste de autenticação |
| **1.12.0** | Observabilidade | OpenTelemetry span integration (placeholder como MetricsMiddleware), mais métricas |
| **1.13.0** | SDK Cookbook | Exemplos reais de fluxos completos em `docs/cookbook/` |
| **1.14+** | Segurança | Dependabot config, token rotation helper, audit logging |

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

FASE 4+ — Backlog
  ├── 1.11.0: Debug tools
  ├── 1.12.0: Observabilidade
  ├── 1.13.0: Cookbook
  └── 1.14+: Segurança
```

## Notas Técnicas

- **Pint**: `composer require --dev laravel/pint` e criar `pint.json` com preset `laravel`
- **Coverage**: CI já instala pcov; basta `pest --coverage --min=90` e `coverage: pcov` no setup-php
- **Release workflow**: `softprops/action-gh-release@v2` com `generate_release_notes: true`
- **transfeera:check**: POST /authorization com dry-run (sem credenciais válidas não dá, mas validar reachability)
- **Config validation**: `ServiceProvider::boot()` → validar estrutura da config, emitir warning
