# AGENTS.md — laravel-transfeera

Instruções para qualquer agente de IA (Hermes, Claude Code, Copilot, etc.) que for trabalhar neste repositório. Leia este arquivo por completo antes de propor ou aplicar qualquer mudança.

## O que é este projeto

Pacote Laravel (`flaviomoreir4/laravel-transfeera`) versão **v1.15.0** — SDK completo da API Transfeera cobrindo **7 domínios**: Pagamentos, Recebimentos, Pix Automático, Webhooks, Conta Certa/Validações, Hub de Contas e MED/Infrações. É consumido como dependência por projetos Laravel de terceiros — não tem acoplamento a nenhuma aplicação específica.

Documentação oficial da API (consultar antes de tocar em qualquer Resource — nunca inferir schema por analogia):
- https://docs.transfeera.dev/reference/endpoints
- https://docs.transfeera.dev/llms.txt (índice completo)

## Setup

```bash
composer install
cp .env.example .env.testing   # se aplicável a testbench
```

Requer PHP 8.3+, Laravel 12+ como dependência de teste (`orchestra/testbench`).

## Comandos de verificação

```bash
composer test          # Pest 5 (259 testes, 396 asserções)
composer analyse       # Larastan / PHPStan level 8 — zero erros
composer rector        # Rector dry-run (verifica)
composer rector-fix    # Rector (aplica correções)
composer format        # Pint / PSR-12
```

Se algum desses scripts não existir ainda no `composer.json`, criá-lo — não pular a verificação manualmente.

## Arquitetura (não desviar sem propor e validar antes)

- **Namespace raiz**: `FlavioMoreir4\Transfeera`.
- **Service Provider**: `TransfeeraServiceProvider`. **Facade**: `Transfeera`. **Config**: `config/transfeera.php` (tag `transfeera-config`).
- **Estrutura de diretórios**:
  - `src/Resources/` — 7 subdiretórios por domínio: `Payments`, `Receivables`, `PixAutomatico`, `ContaCerta`, `Accounts`, `Infractions`, `Webhooks`
  - `src/DTOs/` — 15 Request DTOs (readonly classes)
  - `src/DTOs/Response/` — 15 Response DTOs (com `createFromApi()`)
  - `src/Auth/TokenManager.php` — OAuth2 `client_credentials` com cache
  - `src/Http/Connector.php` — requisições HTTP + middlewares
  - `src/Http/MtlsConfigurator.php` — mTLS condicional
  - `src/Http/Middleware/` — `LoggingMiddleware`, `MetricsMiddleware`
  - `src/Exceptions/` — 11 classes (base + HTTP + 6 de domínio)
- **Autenticação**: OAuth2 `client_credentials` com token cacheado (renova ~60s antes de `expires_in`). Em produção, `Connector` exige mTLS (cert/key configuráveis) para Pagamentos e Conta Certa — sandbox não precisa.
- **DTOs**: **readonly classes nativas do PHP**, sem `spatie/laravel-data` ou similar. Só reconsiderar com justificativa escrita se a duplicação ficar grande demais.
- **Valores monetários**: sempre em centavos (inteiro), nunca float.
- **Multi-tenancy via accountId**: todos os Resources aceitam `$accountId` opcional para operar em nome de uma conta digital (Hub de Contas). O `TokenManager` adiciona `scope=account_id:{accountId}` ao token.

### Recursos Implementados

| Domínio | Resources | Status |
|---------|-----------|--------|
| **Pagamentos** | `BatchResource`, `TransferResource`, `BilletResource`, `BankResource`, `StatementResource`, `RecurrenceResource`, `PixResource` | ✅ 7 |
| **Recebimentos** | `PixKeyResource`, `PixQrCodeResource`, `PixCashInResource`, `ChargeResource`, `PaymentLinkResource` | ✅ 5 |
| **Pix Automático** | `AuthorizationResource`, `PaymentIntentResource` | ✅ 2 |
| **Webhooks** | `PaymentsWebhookResource`, `ReceivablesWebhookResource`, `ContaCertaWebhookResource` | ✅ 3 |
| **Conta Certa** | `ValidationResource`, `BankResource` | ✅ 2 |
| **Hub de Contas** | `AccountResource` | ✅ 1 |
| **MED/Infrações** | `InfractionResource` | ✅ 1 |
| **Total** | 24 Resources | ✅ |

### Exceptions (11)

| Tipo | Classe | Disparo |
|------|--------|---------|
| Base | `TransfeeraException` | Abstrata — não instanciar diretamente |
| HTTP 401 | `TransfeeraAuthenticationException` | Token inválido/expirado |
| HTTP 422 | `TransfeeraValidationException` | Dados inválidos — use `$e->getErrors()` |
| HTTP 429 | `TransfeeraRateLimitException` | Rate limit — use `$e->getRetryAfter()` |
| Domínio: Pagamentos | `PaymentException` | Erros na API de Pagamentos |
| Domínio: Recebimentos | `ReceivableException` | Erros na API de Recebimentos |
| Domínio: Pix Automático | `PixAutomaticoException` | Erros na API de Pix Automático |
| Domínio: Conta Certa | `ContaCertaException` | Erros na API de Conta Certa |
| Domínio: Hub de Contas | `AccountException` | Erros na API de Contas |
| Domínio: MED | `InfractionException` | Erros na API de MED/Infrações |

### Middlewares

- **`LoggingMiddleware`**: log de requests/responses com sanitização de dados sensíveis, truncamento de payloads grandes, níveis configuráveis por domínio.
- **`MetricsMiddleware`**: contadores por domínio/método/status, histogramas de duração, taxa de erro. `recordMetric()` é um placeholder interno — **zero dependências externas**. A frase "pronto para Prometheus/StatsD" significa apenas que o formato de saída é compatível (comentários no código); a integração real exige que o app Laravel registre um driver concreto.

## Dependências: minimalismo é regra, não sugestão

Antes de adicionar qualquer pacote ao `composer.json`, perguntar: "o Laravel já resolve isso?"

**Produção** — zero dependências externas além do próprio Laravel:
- `illuminate/support`, `illuminate/http`, `illuminate/contracts` (via `Http::` facade — já embute Guzzle)
- Cache do Laravel para o token OAuth2

**Dev** (apenas):
- `orchestra/testbench`, `pestphp/pest`, `larastan/larastan`, `rector/rector`

Nenhum client HTTP alternativo (Saloon, Guzzle direto), nenhum DTO builder de terceiros, nenhuma lib de métricas externa.

Se a tentação de adicionar uma dependência nova surgir, **parar e justificar por escrito** (o que resolve, custo de não usar, peso que adiciona) antes de editar o `composer.json`.

## Testes

- **Pest 5** — 199 testes, 283 asserções (v1.9.0)
- Cobertura: Resources (Feature), DTOs/Exceptions/Listeners/TokenManager/Connector (Unit)
- `Http::fake()` com fixtures extraídas de payloads reais da documentação (`tests/Fixtures/`) — nunca inventar formato de payload.
- Cobrir sempre: renovação automática de token, seleção de base URL por ambiente/sub-API, mTLS condicional, mapeamento de erro por status HTTP, validação de assinatura de webhook.
- TDD leve: descrever o teste do Resource antes de escrever o Resource.

## Documentação — manter sincronizada a cada mudança relevante

Todo o conteúdo de `docs/` está criado e sincronizado com a v1.9.0:

| Arquivo | Conteúdo |
|---------|----------|
| `docs/pagamentos.md` | Lotes, transferências, boletos, saldo, recorrências |
| `docs/recebimentos.md` | Chaves Pix, QR Codes, Cash-in, cobranças, links |
| `docs/pix-automatico.md` | Autorizações, Payment Intents, webhooks, fluxo |
| `docs/conta-certa.md` | Validações, bancos suportados |
| `docs/hub-contas.md` | Contas digitais, onboarding, tenancy |
| `docs/med.md` | Infrações, análise individual/lote, devolução |
| `docs/webhooks.md` | Rotas, secrets, validação HMAC, listeners |
| `docs/exceptions.md` | Hierarquia completa, catch, métodos úteis |
| `docs/middlewares.md` | Config, logging, métricas, Prometheus |
| `docs/erros.md` | Códigos HTTP, handlers, retry |
| `docs/primeiro-pagamento.md` | Guia passo a passo inicial |
| `docs/primeiro-recebimento.md` | Guia passo a passo inicial |
| `docs/changelog.md` | Histórico de versões (atalho local) |
| `docs/cookbook.md` | Guia prático com 10 receitas (7 domínios) |
| `docs/roadmap.md` | Planejamento de versões futuras |
| `docs/adr/README.md` + 10 ADRs | Registro de Decisões Arquiteturais |
| **`REQUISITOS.md`** | Especificação de requisitos completa (52 RFs, 17 RNFs) |

- Recurso documentado mas não implementado vai para uma seção **"Roadmap"** no respectivo doc de domínio — nunca fica implícito.
- `README.md`: instalacão, config (com aviso de mTLS em produção), exemplo por domínio, tabela de cobertura de endpoints, seção de links oficiais.
- `CHANGELOG.md`: formato **Keep a Changelog** estrito — seções Added/Changed/Deprecated/Removed/Fixed/Security, seção `[Unreleased]` sempre no topo.
- `UPGRADE.md`: guia de migração entre versões MAJOR. Obrigatório quando houver breaking change na API pública do pacote.
- `REQUISITOS.md`: Especificação de Requisitos de Software (SRS) — rastreabilidade RF → código, casos de uso, métricas de qualidade.
- `docs/adr/`: 10 Decisões Arquiteturais no formato Michael Nygard.

## Versionamento e release

- **SemVer estrito**: MAJOR = quebra na API pública do **pacote** (não da API da Transfeera), MINOR = recurso novo retrocompatível, PATCH = correção retrocompatível.
- **Estratégia híbrida**: v1.x contínua com deprecação lenta, v2.0.0 planejada ~2026 Q1.
  - `@deprecated` introduzido em v1.10+ com ~6 meses de aviso antes da remoção em v2.0.
  - Arrays em create/update, métodos `*Raw()` e Connector público mantidos até v2.0.
  - Ver `docs/adr/006-never-break-compatibility.md`.
- **Fluxo**:
  1. Antes da tag: mover `[Unreleased]` do CHANGELOG para versão+data; atualizar `UPGRADE.md` se houver breaking; sincronizar versão no `composer.json` (referência interna, pois o Packagist lê da tag).
  2. Criar tag `vMAJOR.MINOR.PATCH` — nunca antes do passo 1.
  3. Confirmar que o tipo de bump (MAJOR/MINOR/PATCH) corresponde à mudança.
  4. Verificar se Packagist sincronizou (webhook do GitHub).

## Regras de execução

1. Análise antes de ação: consultar `docs/llms.txt` e `reference/endpoints` da Transfeera antes de escrever código — nunca inferir schema por analogia com outro endpoint.
2. Nenhum commit é considerado pronto sem: testes passando, PHPStan limpo, Rector verde, docs/README/CHANGELOG atualizados quando o escopo mudou.
3. Nunca commitar segredos (client secret, certificados mTLS) — `.env` e `.gitignore`.
4. Ao final de qualquer tarefa maior, resumir o que mudou, o que ficou pendente, e propor atualização deste arquivo se necessário.

---

## Última Auditoria

| Item | Valor |
|------|-------|
| **Versão** | v1.15.0 |
| **Commit** | `dc9c1d6` (feat: v1.14.0 — LoggingMiddleware avançado, cookbook expandido) |
| **Testes** | 259 passing, 396 assertions |
| **PHPStan** | Level 8 — 0 erros |
| **Rector** | Clean |
| **Documentação** | 16 docs + README + CHANGELOG + UPGRADE + REQUISITOS — todos sincronizados |
| **Data** | Julho 2025 |
