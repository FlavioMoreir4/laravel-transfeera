# Especificação de Requisitos — Laravel Transfeera SDK

> **Versão:** v1.10.0  
> **Última atualização:** 2025-07-30  
> **Status:** ✅ Implementado

---

## 1. Introdução

### 1.1 Propósito

Este documento especifica os requisitos funcionais e não-funcionais do pacote Laravel `flaviomoreir4/laravel-transfeera`, um SDK Laravel nativo para integração com a API da Transfeera — plataforma brasileira de pagamentos, recebimentos via Pix, boletos e transferências.

O SDK abstrai a complexidade de autenticação OAuth2, mTLS, webhooks e versionamento de API, oferecendo uma interface Laravel idiomática baseada em DTOs tipados, Facades e Service Container.

### 1.2 Escopo

O pacote cobre **7 domínios** da API Transfeera:

| # | Domínio | Abrangência |
|---|---------|-------------|
| 1 | **Pagamentos** | Lotes, transferências, boletos, saldo, extrato, recorrências, consulta Pix |
| 2 | **Recebimentos** | Chaves Pix, QR Codes, cash-in, cobranças, links de pagamento |
| 3 | **Pix Automático** | Autorizações, payment intents |
| 4 | **Webhooks** | Recebimento e validação de notificações (pagamentos, recebimentos, conta certa) |
| 5 | **Conta Certa** | Validação de contas bancárias, consulta de bancos |
| 6 | **Hub de Contas** | Gerenciamento de contas digitais |
| 7 | **MED / Infrações** | Análise e devolução de infrações Pix |

### 1.3 Definições e Abreviações

| Termo | Significado |
|-------|-------------|
| **SDK** | Software Development Kit |
| **mTLS** | Mutual TLS — autenticação mútua via certificado |
| **OAuth2** | Protocolo de autorização — `client_credentials` |
| **DTO** | Data Transfer Object — `readonly class` imutável |
| **HMAC** | Hash-based Message Authentication Code |
| **MED** | Mecanismo Especial de Devolução (infrações Pix) |
| **Pix Automático** | Débito automático via Pix (autorização + pagamento recorrente) |

### 1.4 Público-alvo

- Desenvolvedores Laravel (12.x / 13.x) integrando com a Transfeera
- Mantenedores do pacote
- Revisores de código e auditores de segurança

### 1.5 Referências

- [Documentação oficial da API Transfeera](https://docs.transfeera.dev/reference/endpoints)
- [Índice llms.txt](https://docs.transfeera.dev/llms.txt)
- [Keep a Changelog](https://keepachangelog.com/)
- [Semantic Versioning 2.0.0](https://semver.org/)

---

## 2. Requisitos de Produto

### 2.1 Perspectiva do Produto

O SDK é um **pacote Laravel** consumido como dependência via Composer. Não é uma aplicação standalone — depende de um projeto Laravel hospedeiro para funcionar. Opera como uma camada de abstração entre o Laravel e a API HTTP da Transfeera.

```
┌─────────────────────────────────────┐
│        Aplicação Laravel            │
│  ┌───────────────────────────────┐  │
│  │   Transfeera Facade / Client  │  │
│  │   ┌───────────────────────┐   │  │
│  │   │  Resources (24)       │   │  │
│  │   │  DTOs (26)            │   │  │
│  │   │  Exceptions (11)      │   │  │
│  │   └────────┬──────────────┘   │  │
│  │            │ HTTP              │  │
│  │   ┌────────▼──────────────┐   │  │
│  │   │  Connector            │   │  │
│  │   │  ├─ TokenManager      │   │  │
│  │   │  ├─ MtlsConfigurator  │   │  │
│  │   │  ├─ LoggingMiddleware │   │  │
│  │   │  └─ MetricsMiddleware │   │  │
│  │   └────────┬──────────────┘   │  │
│  └────────────┼──────────────────┘  │
└───────────────┼─────────────────────┘
                │ HTTPS (+ mTLS em produção)
┌───────────────▼─────────────────────┐
│       API Transfeera                │
│  (auth / payments / conta-certa)    │
└─────────────────────────────────────┘
```

### 2.2 Características do Usuário

| Perfil | Experiência | Uso típico |
|--------|-------------|------------|
| Desenvolvedor Laravel | Intermediário a avançado | Integração de pagamentos no sistema |
| Mantenedor do pacote | Avançado | Adicionar novos endpoints, corrigir bugs |
| DevOps | Avançado | Configurar mTLS, CI/CD, monitoramento |

### 2.3 Premissas

- PHP 8.3+ com extensões cURL, JSON, OpenSSL
- Laravel 12.x ou 13.x com HTTP Client habilitado
- Aplicação consumidora possui conta ativa na Transfeera
- Produção exige certificado mTLS válido (fornecido pela Transfeera)
- O token OAuth2 tem `expires_in` mínimo de 60 segundos
- O cache do Laravel está operacional (qualquer store compatível)

### 2.4 Dependências

**Produção:**
| Pacote | Versão | Uso |
|--------|--------|-----|
| `php` | ^8.3 \|\| ^8.4 | Runtime |
| `illuminate/support` | ^12.0 \|\| ^13.0 | ServiceProvider, Facade, helpers |
| `illuminate/http` | ^12.0 \|\| ^13.0 | HTTP Client (Guzzle wrapper) |
| `illuminate/contracts` | ^12.0 \|\| ^13.0 | Cache, Events contracts |

**Desenvolvimento:**
| Pacote | Uso |
|--------|-----|
| `orchestra/testbench` | Testes em ambiente Laravel isolado |
| `pestphp/pest` | Test runner |
| `larastan/larastan` | PHPStan level 8 |
| `rector/rector` | Refatoração automática |
| `laravel/pint` | PSR-12 formatting |

---

## 3. Requisitos Funcionais

### 3.1 Autenticação e Segurança

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-01 | Autenticar via OAuth2 `client_credentials` | 🔴 Alta | ✅ |
| RF-02 | Cachear token de acesso com renovação automática antes da expiração | 🔴 Alta | ✅ |
| RF-03 | Usar lock de cache para evitar múltiplas renovações concorrentes | 🔴 Alta | ✅ |
| RF-04 | Suportar store de cache configurável | 🟡 Média | ✅ |
| RF-05 | Renovar token com scope `account_id` para multi-tenancy | 🟡 Média | ✅ |
| RF-06 | Limpar cache manualmente | 🟢 Baixa | ✅ |
| RF-07 | Aplicar mTLS automaticamente em produção nas APIs de Pagamentos e Conta Certa | 🔴 Alta | ✅ |
| RF-08 | Validar existencia do certificado mTLS antes de enviar requisição | 🟡 Média | ✅ |
| RF-09 | Validar configuração no boot e emitir warnings | 🟢 Baixa | ✅ |

### 3.2 HTTP Client

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-10 | Enviar requisições GET, POST, PUT, PATCH, DELETE | 🔴 Alta | ✅ |
| RF-11 | Selecionar base URL automaticamente por domínio e ambiente | 🔴 Alta | ✅ |
| RF-12 | Injetar token Bearer em todas as requisições | 🔴 Alta | ✅ |
| RF-13 | Configurar timeout por requisição | 🟡 Média | ✅ |
| RF-14 | Configurar retry automático com backoff | 🟡 Média | ✅ |
| RF-15 | Extrair headers de rate limit (Retry-After, X-RateLimit-*) nas exceptions 429 | 🟡 Média | ✅ |
| RF-16 | Logging de requisições com sanitização | 🟢 Baixa | ✅ |
| RF-17 | Placeholder para métricas (Prometheus/StatsD) | 🟢 Baixa | ✅ |

### 3.3 Mapeamento de Erros

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-18 | Mapear HTTP 401 → `TransfeeraAuthenticationException` | 🔴 Alta | ✅ |
| RF-19 | Mapear HTTP 422 → `TransfeeraValidationException` com `getErrors()` | 🔴 Alta | ✅ |
| RF-20 | Mapear HTTP 429 → `TransfeeraRateLimitException` com dados de rate limit | 🔴 Alta | ✅ |
| RF-21 | Mapear erros por domínio: PaymentException, ReceivableException, etc. | 🔴 Alta | ✅ |
| RF-22 | Exceção base `TransfeeraException` com acesso ao payload bruto | 🟡 Média | ✅ |

### 3.4 Pagamentos (7 Resources)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-23 | CRUD de lotes (create, list, get, update, delete) | 🔴 Alta | ✅ |
| RF-24 | CRUD de transferências dentro de lote (create, get, update, delete) | 🔴 Alta | ✅ |
| RF-25 | CRUD de boletos (create, list, get, update, delete) | 🔴 Alta | ✅ |
| RF-26 | Listar bancos suportados | 🟡 Média | ✅ |
| RF-27 | Consultar saldo e extrato | 🔴 Alta | ✅ |
| RF-28 | CRUD de recorrências (create, list, get, update, delete) | 🟡 Média | ✅ |
| RF-29 | Consultar chave Pix DICT e parsear EMV | 🟡 Média | ✅ |

### 3.5 Recebimentos (5 Resources)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-30 | CRUD de chaves Pix (create, list, get, update, delete) | 🔴 Alta | ✅ |
| RF-31 | CRUD de QR Codes Pix (create, list, get) | 🔴 Alta | ✅ |
| RF-32 | Listar Pix recebidos (cash-in) | 🟡 Média | ✅ |
| RF-33 | CRUD de cobranças + download PDF | 🔴 Alta | ✅ |
| RF-34 | CRUD de links de pagamento | 🟡 Média | ✅ |

### 3.6 Pix Automático (2 Resources)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-35 | Gerenciar autorizações (create, list, get, revoke) | 🔴 Alta | ✅ |
| RF-36 | Gerenciar payment intents (create, list, get, cancel) | 🔴 Alta | ✅ |

### 3.7 Webhooks (3 Endpoints)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-37 | Receber webhooks de pagamentos, recebimentos e conta certa | 🔴 Alta | ✅ |
| RF-38 | Validar assinatura HMAC-SHA256 | 🔴 Alta | ✅ |
| RF-39 | Suportar regra de cálculo diferente para recebimentos | 🟡 Média | ✅ |
| RF-40 | Disparar evento Laravel `TransfeeraWebhookReceived` | 🟡 Média | ✅ |
| RF-41 | Rotas carregadas automaticamente pelo ServiceProvider | 🔴 Alta | ✅ |
| RF-42 | Secrets configuráveis por domínio com fallback global | 🟡 Média | ✅ |
| RF-43 | Gerenciar URLs de webhook via API (CRUD) | 🟡 Média | ✅ |

### 3.8 Conta Certa (2 Resources)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-44 | Validar conta bancária | 🔴 Alta | ✅ |
| RF-45 | Listar bancos suportados | 🟡 Média | ✅ |

### 3.9 Hub de Contas (1 Resource)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-46 | CRUD de contas digitais (create, list, get, update, delete) | 🟡 Média | ✅ |

### 3.10 MED / Infrações (1 Resource)

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-47 | Analisar infração individual | 🟡 Média | ✅ |
| RF-48 | Analisar infrações em lote | 🟡 Média | ✅ |
| RF-49 | Devolução individual | 🟡 Média | ✅ |
| RF-50 | Devolução em lote | 🟡 Média | ✅ |

### 3.11 Comandos Artisan

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-51 | Comando `transfeera:install` para publicar config e guia inicial | 🟢 Baixa | ✅ |
| RF-52 | Comando `transfeera:check` para health check de conectividade | 🟡 Média | ✅ |

### 3.12 CI/CD

| ID | Requisito | Prioridade | Status |
|----|-----------|-----------|--------|
| RF-53 | CI matrix PHP 8.3/8.4 × Laravel 12/13 | 🔴 Alta | ✅ |
| RF-54 | PHPStan level 8 — zero erros | 🔴 Alta | ✅ |
| RF-55 | Rector — zero issues | 🟡 Média | ✅ |
| RF-56 | Pint — PSR-12 formatting | 🟡 Média | ✅ |
| RF-57 | Coverage mínimo 90% (PHP 8.3) | 🟡 Média | ✅ |
| RF-58 | Release automática ao push de tag | 🟢 Baixa | ✅ |

---

## 4. Requisitos Não-Funcionais

### 4.1 Compatibilidade

| ID | Requisito | Critério de Aceitação |
|----|-----------|----------------------|
| RNF-01 | PHP 8.3+ | `composer install` sem erros em PHP 8.3 e 8.4 |
| RNF-02 | Laravel 12.x e 13.x | Testes passam em ambas as versões |
| RNF-03 | Zero dependências externas de produção além do Laravel | `composer show --tree` sem dependências não-Laravel |
| RNF-04 | Cache store do Laravel | Funciona com file, redis, array, database |

### 4.2 Segurança

| ID | Requisito | Critério de Aceitação |
|----|-----------|----------------------|
| RNF-05 | Token OAuth2 nunca exposto em logs | Sanitização antes do logging |
| RNF-06 | mTLS obrigatório em produção | Exception clara se certificado ausente |
| RNF-07 | Comparação timing-safe de assinatura | `hash_equals()` usado em webhooks |
| RNF-08 | Payloads sensíveis truncados em logs | Config `logHeaders` controla exposição |

### 4.3 Desempenho

| ID | Requisito | Critério de Aceitação |
|----|-----------|----------------------|
| RNF-09 | Cache de token evita renovação a cada request | TokenManager usa cache |
| RNF-10 | Lock evita N renovações concorrentes | Apenas 1 request de token entre N concorrentes |
| RNF-11 | Timeout configurável (default 30s) | `Connector` passa timeout ao HTTP Client |

### 4.4 Manutenibilidade

| ID | Requisito | Critério de Aceitação |
|----|-----------|----------------------|
| RNF-12 | 199+ testes, 283+ asserções | `composer test` passa |
| RNF-13 | PHPStan level 8 — 0 erros | `composer phpstan` sem erros |
| RNF-14 | Rector sem dead code | `composer rector` aprovado |
| RNF-15 | PSR-12 formatting | `composer format` sem alterações |
| RNF-16 | DTOs imutáveis (`readonly class`) | `new ReflectionClass()` confirma `readonly` |
| RNF-17 | Sem dependências obsoletas | `composer outdated` limpo |

### 4.5 Documentação

| ID | Requisito | Critério de Aceitação |
|----|-----------|----------------------|
| RNF-18 | README com badges, instalação, config, exemplos por domínio | Inspeção visual |
| RNF-19 | CHANGELOG.md em formato Keep a Changelog | Seções Added/Changed/Deprecated/Removed/Fixed/Security |
| RNF-20 | 14 documentos em `docs/` cobrindo todos os domínios | `ls docs/ | wc -l` ≥ 14 |
| RNF-21 | AGENTS.md atualizado com estado real do projeto | Versão, contagens, estrutura |
| RNF-22 | REQUISITOS.md com rastreabilidade RF → código | Tabelas de requisitos |

---

## 5. Rastreabilidade Requisito → Implementação

### 5.1 Mapa de Recursos

| Domínio | Resource | Arquivo | RFs |
|---------|----------|---------|-----|
| Pagamentos | `BatchResource` | `src/Resources/Payments/BatchResource.php` | RF-23 |
| Pagamentos | `TransferResource` | `src/Resources/Payments/TransferResource.php` | RF-24 |
| Pagamentos | `BilletResource` | `src/Resources/Payments/BilletResource.php` | RF-25 |
| Pagamentos | `BankResource` | `src/Resources/Payments/BankResource.php` | RF-26 |
| Pagamentos | `StatementResource` | `src/Resources/Payments/StatementResource.php` | RF-27 |
| Pagamentos | `RecurrenceResource` | `src/Resources/Payments/RecurrenceResource.php` | RF-28 |
| Pagamentos | `PixResource` | `src/Resources/Payments/PixResource.php` | RF-29 |
| Recebimentos | `PixKeyResource` | `src/Resources/Receivables/PixKeyResource.php` | RF-30 |
| Recebimentos | `PixQrCodeResource` | `src/Resources/Receivables/PixQrCodeResource.php` | RF-31 |
| Recebimentos | `PixCashInResource` | `src/Resources/Receivables/PixCashInResource.php` | RF-32 |
| Recebimentos | `ChargeResource` | `src/Resources/Receivables/ChargeResource.php` | RF-33 |
| Recebimentos | `PaymentLinkResource` | `src/Resources/Receivables/PaymentLinkResource.php` | RF-34 |
| Pix Automático | `AuthorizationResource` | `src/Resources/PixAutomatico/AuthorizationResource.php` | RF-35 |
| Pix Automático | `PaymentIntentResource` | `src/Resources/PixAutomatico/PaymentIntentResource.php` | RF-36 |
| Webhooks | `PaymentsWebhookResource` | `src/Resources/Webhooks/PaymentsWebhookResource.php` | RF-43 |
| Webhooks | `ReceivablesWebhookResource` | `src/Resources/Webhooks/ReceivablesWebhookResource.php` | RF-43 |
| Webhooks | `ContaCertaWebhookResource` | `src/Resources/Webhooks/ContaCertaWebhookResource.php` | RF-43 |
| Webhooks | `WebhookController` | `src/Http/Controllers/WebhookController.php` | RF-37, RF-38, RF-40 |
| Webhooks | `SignatureValidator` | `src/Webhooks/SignatureValidator.php` | RF-38, RF-39 |
| Conta Certa | `ValidationResource` | `src/Resources/ContaCerta/ValidationResource.php` | RF-44 |
| Conta Certa | `BankResource` | `src/Resources/ContaCerta/BankResource.php` | RF-45 |
| Hub de Contas | `AccountResource` | `src/Resources/Accounts/AccountResource.php` | RF-46 |
| MED | `InfractionResource` | `src/Resources/Infractions/InfractionResource.php` | RF-47 a RF-50 |

### 5.2 Mapa de Infraestrutura

| Componente | Arquivo | RFs |
|------------|---------|-----|
| `TransfeeraServiceProvider` | `src/TransfeeraServiceProvider.php` | RF-09, RF-51, RF-52 |
| `TransfeeraClient` | `src/TransfeeraClient.php` | Fachada para todos os RFs de domínio |
| `Connector` | `src/Http/Connector.php` | RF-10 a RF-22 |
| `TokenManager` | `src/Auth/TokenManager.php` | RF-01 a RF-06 |
| `AccessToken` | `src/Auth/AccessToken.php` | RF-01 |
| `MtlsConfigurator` | `src/Http/MtlsConfigurator.php` | RF-07, RF-08 |
| `LoggingMiddleware` | `src/Http/Middleware/LoggingMiddleware.php` | RF-16 |
| `MetricsMiddleware` | `src/Http/Middleware/MetricsMiddleware.php` | RF-17 |
| `CheckCommand` | `src/Console/Commands/CheckCommand.php` | RF-52 |
| `InstallCommand` | `src/Console/Commands/InstallCommand.php` | RF-51 |

---

## 6. Casos de Uso Principais

### UC-01: Criar e processar um lote de pagamentos

```php
// 1. Criar lote
$batch = Transfeera::batches()->create(['name' => 'Lote', 'type' => 'manual']);

// 2. Adicionar transferências
Transfeera::transfers($batch['id'])->create([
    'amount' => 150000,
    'pix_key' => 'cliente@email.com',
    'pix_key_type' => 'email',
]);

// 3. Processar (via API — endpoint separado)
```

### UC-02: Validar conta bancária (Conta Certa)

```php
$result = Transfeera::contaCertaValidations()->validate([
    'bank_code' => '341',
    'agency' => '1234',
    'account' => '56789-0',
    'document' => '123.456.789-00',
]);
```

### UC-03: Receber e processar webhook

```php
// No EventServiceProvider:
protected $listen = [
    TransfeeraWebhookReceived::class => [
        MinhaListener::class,
    ],
];

// Na listener:
public function handle(TransfeeraWebhookReceived $event): void
{
    match ($event->domain) {
        'payments' => $this->processPaymentWebhook($event->payload),
        'receivables' => $this->processReceivableWebhook($event->payload),
        'conta_certa' => $this->processValidationWebhook($event->payload),
    };
}
```

---

## 7. Restrições Técnicas

1. **Sem dependências externas de produção** — Apenas `illuminate/*` é permitido. A tentação de adicionar `saloon`, `spatie/laravel-data`, ou client HTTP alternativos deve ser resistida.
2. **DTOs nativos** — `readonly class` do PHP 8.3+, sem bibliotecas de mapeamento.
3. **Valores monetários em centavos** — Inteiros, nunca float.
4. **Multi-tenancy via `accountId`** — Todas as Resources aceitam `$accountId` opcional.
5. **Estratégia híbrida** — v1.x contínua com deprecação lenta, v2.0.0 planejada ~2026 Q1. Arrays em `create`/`update`, métodos `*Raw()` e Connector público mantidos até v2.0.
6. **Testes com `Http::fake()`** — Sem chamadas reais à API durante testes unitários/feature.

---

## 8. Métricas de Qualidade

| Métrica | Alvo | Atual |
|---------|------|-------|
| Cobertura de testes | ≥ 90% | ✅ (cobertura via CI) |
| PHPStan | Level 8, 0 erros | ✅ |
| Rector | OK | ✅ |
| Pint | PSR-12 | ✅ |
| Testes passando | 100% | ✅ 201/201 |
|| Asserções | ≥ 250 | ✅ 283 |
| Resources implementados | 24/24 | ✅ |
| Documentos em `docs/` | 14+ | ✅ 15 |

---

## 9. Versões e Histórico

| Versão | Data | Principais Mudanças |
|--------|------|---------------------|
| 1.0.0 | — | Lançamento inicial |
| 1.7.0 | — | Exceptions tipadas por domínio, testes de integração |
| 1.8.0 | 2025-07-30 | Cobertura de testes: error handling, middlewares, concorrência |
| 1.8.1 | 2025-07-30 | Pint, scripts, 14 novos testes, docs |
| **1.9.0** | 2025-07-30 | Release workflow, check command, rate limit headers, config validation, ServiceProvider/InstallCommand tests |
| **1.10.0** | 2025-07-30 | Response DTOs, Laravel 13 migration guide, integration tests, rate limit docs |
