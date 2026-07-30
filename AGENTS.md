# AGENTS.md — laravel-transfeera

Instruções para qualquer agente de IA (Hermes, Claude Code, Copilot, etc.) que for trabalhar neste repositório. Leia este arquivo por completo antes de propor ou aplicar qualquer mudança.

## O que é este projeto

Pacote Laravel (`flaviomoreir4/laravel-transfeera`) que cobre a API da Transfeera: **Pagamentos, Recebimentos, Pix Automático e Conta Certa/Validações** (incluindo Hub de Contas e MED/Infrações). É consumido como dependência por projetos Laravel de terceiros — não tem acoplamento a nenhuma aplicação específica.

Documentação da API a consultar antes de tocar em qualquer Resource (nunca inferir schema por analogia):
- https://docs.transfeera.dev/reference/endpoints
- https://docs.transfeera.dev/llms.txt (índice completo)

## Setup

```bash
composer install
cp .env.example .env.testing   # se aplicável a testbench
```

Requer PHP 8.3+, Laravel 12+ como dependência de teste (`orchestra/testbench`).

## Comandos de verificação (rodar sempre antes de propor uma mudança como pronta)

```bash
composer test          # Pest 4
composer analyse       # Larastan / PHPStan nível alto
composer rector-check  # Rector em modo dry-run
composer format        # Pint / PSR-12
```

Se algum desses scripts não existir ainda no `composer.json`, criá-lo — não pular a verificação manualmente.

## Arquitetura (não desviar sem propor e validar antes)

- Namespace raiz: `FlavioMoreir4\Transfeera`.
- Service Provider: `TransfeeraServiceProvider`. Facade: `Transfeera`. Config: `config/transfeera.php` (tag `transfeera-config`).
- Estrutura: `src/Resources/{Payments,Receivables,PixAutomatico,ContaCerta,Accounts,Infractions,Webhooks}/...`, `src/DTOs/` (DTOs de Request + Response), `src/Auth/TokenManager.php`, `src/Http/Connector.php` + `MtlsConfigurator.php`, `src/Exceptions/`.
- Autenticação: OAuth2 `client_credentials` com token cacheado (renovar ~60s antes de `expires_in`). Em produção, `Connector` exige mTLS (cert/key configuráveis) para pagamentos e conta-certa — sandbox não precisa.
- DTOs: **readonly classes nativas do PHP**, sem `spatie/laravel-data` ou similar. Só reconsiderar com justificativa escrita se a duplicação de código ficar grande demais.
- Valores monetários: sempre em centavos (inteiro), nunca float.

## Dependências: minimalismo é regra, não sugestão

Antes de adicionar qualquer pacote ao `composer.json`, perguntar: "o Laravel já resolve isso?" Na prática:

- **Usar**: `illuminate/support`/`http`/`contracts` (via `Http::` facade — já embute Guzzle, não precisa de client externo), Cache do Laravel para o token, `orchestra/testbench` + `pestphp/pest` + `larastan/larastan` + `rector/rector` (todos dev-only).
- **Evitar**: clients HTTP alternativos (Saloon, Guzzle direto), DTO builders de terceiros, qualquer lib que só economize poucas linhas que `Str`/`Arr` já resolvem.
- Se a tentação de adicionar uma dependência nova surgir, **parar e justificar por escrito** (o que resolve, custo de não usar, peso que adiciona) antes de editar o `composer.json`.

## Testes

- Pest 4. Mínimo 90% de cobertura em Resources, 100% em `TokenManager` e tratamento de erro.
- `Http::fake()` com fixtures extraídas de payloads reais da documentação (`tests/Fixtures/`) — **nunca inventar formato de payload**.
- Cobrir sempre: renovação automática de token, seleção de base URL por ambiente/sub-API, mTLS condicional, mapeamento de erro por status HTTP, validação de assinatura de webhook.
- TDD leve: descrever o teste do Resource antes de escrever o Resource.

## Documentação — manter sincronizada a cada mudança relevante

- `docs/`: um arquivo por domínio de recurso, exemplos extraídos dos testes reais. Recurso documentado mas não implementado vai para uma seção "Roadmap", nunca fica implícito.
- `README.md`: instalação, config (com aviso de mTLS em produção), exemplo por domínio, tabela de cobertura de endpoints.
- `CHANGELOG.md`: formato Keep a Changelog (https://keepachangelog.com/pt-BR/1.1.0/) — seções Added/Changed/Deprecated/Removed/Fixed/Security, seção `[Unreleased]` sempre no topo.
- `UPGRADE.md`: obrigatório quando houver breaking change na API pública do pacote.

## Versionamento e release

- SemVer estrito (https://semver.org/lang/pt-BR/): MAJOR = quebra na API pública do **pacote** (não da API da Transfeera), MINOR = recurso novo retrocompatível, PATCH = correção retrocompatível.
- Antes de criar a tag: mover `[Unreleased]` do CHANGELOG para a versão nova com data, atualizar `UPGRADE.md` se houver breaking change, sincronizar qualquer referência interna de versão (docblock/constante) — o Packagist lê a versão pela tag Git, não pelo `composer.json`, então esse campo (se existir) é só referência interna.
- Tag no formato `vMAJOR.MINOR.PATCH`, criada só depois do commit com CHANGELOG/UPGRADE atualizados — nunca antes.

## Regras de execução

1. Análise antes de ação: abrir a página de referência do endpoint na doc da Transfeera antes de escrever código, mesmo que pareça óbvio por semelhança com outro endpoint já implementado.
2. Nenhum PR/commit é considerado pronto sem: testes passando, Larastan limpo, Rector aplicado, docs/README/CHANGELOG atualizados quando o escopo mudou.
3. Nunca commitar segredos (client secret, certificados mTLS) — usar `.env` e `.gitignore`, e conferir isso antes de qualquer commit.
4. Ao final de qualquer tarefa maior, resumir o que mudou, o que ficou pendente, e se algo aqui neste arquivo ficou desatualizado (e propor a atualização).

---

## Estado Atual do Projeto (Julho 2025)

### Versão Atual: **v1.7.0** (última tag)

### Recursos Implementados

| Domínio | Resources | Status |
|---------|-----------|--------|
| **Pagamentos** | BatchResource, TransferResource, BilletResource, BankResource, StatementResource, RecurrenceResource, PixResource | ✅ |
| **Recebimentos** | PixKeyResource, PixQrCodeResource, PixCashInResource, ChargeResource, PaymentLinkResource | ✅ |
| **Pix Automático** | AuthorizationResource, PaymentIntentResource | ✅ |
| **Webhooks** | PaymentsWebhookResource, ReceivablesWebhookResource, ContaCertaWebhookResource | ✅ |
| **Conta Certa** | ValidationResource, BankResource | ✅ |
| **Hub de Contas** | AccountResource | ✅ |
| **MED/Infrações** | InfractionResource | ✅ |

### DTOs Implementados

**Request DTOs (15):**
- BatchDTO, TransferDTO, BilletDTO, RecurrenceDTO, StatementReportDTO
- PixKeyDTO, PixQrCodeStaticDTO, PixQrCodeImmediateDTO, PixQrCodeDueDTO, PaymentLinkDTO
- ChargeDTO, AuthorizationDTO, PaymentIntentDTO
- ValidationDTO, AccountDTO, InfractionAnalysisDTO, InfractionBatchAnalysisDTO

**Response DTOs (11):**
- BaseResponseDTO (abstract), BatchResponseDTO, TransferResponseDTO, PixKeyResponseDTO
- PixQrCodeResponseDTO, PixCashInResponseDTO, ChargeResponseDTO, PaymentLinkResponseDTO
- AuthorizationResponseDTO, PaymentIntentResponseDTO, BankResponseDTO

### Exceptions (11 total)
- Base: TransfeeraException
- HTTP 401: TransfeeraAuthenticationException
- HTTP 422: TransfeeraValidationException
- HTTP 429: TransfeeraRateLimitException
- Domínio: PaymentException, ReceivableException, PixAutomaticoException, ContaCertaException, AccountException, InfractionException

### Middlewares
- LoggingMiddleware: log de requests/responses com sanitização, truncamento, níveis configuráveis
- MetricsMiddleware: contadores, histogramas, taxa de erro (pronto para Prometheus/StatsD)

### Testes
- **118 testes, 160 asserções** — todos passando
- Cobertura: Resources (Feature), DTOs/Exceptions/Listeners/TokenManager/Connector (Unit)

### Qualidade
- PHPStan nível 8: **0 erros**
- Rector: **clean**
- Composer scripts: `test`, `analyse`, `rector`, `rector-fix`, `format`

---

## Levantamento de Documentação Atual vs Código

### `docs/` — Arquivos Existentes

| Arquivo | Estado | Observações |
|---------|--------|-------------|
| `primeiro-pagamento.md` | ✅ Atualizado | Exemplos com DTOs, Service pattern, Controller |
| `primeiro-recebimento.md` | ✅ Atualizado | Chaves Pix, QR Codes, Charges, Links, Cash-in |
| `webhooks.md` | ✅ Atualizado | Rotas, secrets, validação, listeners, retry, testes |
| `erros.md` | ✅ Atualizado | Hierarquia, handlers, retry, códigos, debug |
| `changelog.md` | ✅ Atualizado | Histórico até v1.7.0, seção [Unreleased] |

### `README.md` — Estado Atual
- ✅ Badges (versão, PHP, Laravel, CI, Tests, PHPStan, Rector)
- ✅ Instalação, configuração .env (com aviso mTLS)
- ✅ Exemplos por domínio (Pagamentos, Recebimentos, Pix Automático, Webhooks, Conta Certa, Hub de Contas, MED)
- ✅ Documentação avançada (links para docs/)
- ✅ Tabela de cobertura de endpoints (4 fases)
- ✅ Seção de testes, análise estática, changelog, contribuição, licença

### `CHANGELOG.md` — Estado Atual
- ✅ Formato Keep a Changelog
- ✅ Seções: Added/Changed/Deprecated/Removed/Fixed/Security
- ✅ Ordem cronológica reversa
- ✅ Versões: 1.0.0 até 1.7.0
- ✅ Seção `[Unreleased]` no topo com planejamento para 1.7.0
- ✅ Breaking changes marcados

### Divergências Identificadas

| Item | Documentação | Código Real | Ação Necessária |
|------|-------------|-------------|-----------------|
| `docs/pix-automatico.md` | ❌ Não existe | AuthorizationResource, PaymentIntentResource implementados | Criar arquivo |
| `docs/pagamentos.md` | ❌ Não existe | 7 Resources implementados | Criar arquivo |
| `docs/recebimentos.md` | ❌ Não existe | 5 Resources implementados | Criar arquivo |
| `docs/conta-certa.md` | ❌ Não existe | ValidationResource, BankResource | Criar arquivo |
| `docs/hub-contas.md` | ❌ Não existe | AccountResource | Criar arquivo |
| `docs/med.md` | ❌ Não existe | InfractionResource | Criar arquivo |
| `docs/exceptions.md` | ❌ Não existe | 11 exceptions tipadas | Criar arquivo |
| `docs/middlewares.md` | ❌ Não existe | LoggingMiddleware, MetricsMiddleware | Criar arquivo |
| `UPGRADE.md` | ❌ Não existe | Breaking changes em v1.2.0 (drop Laravel 11/8.2) | Criar arquivo |
| `CONTRIBUTING.md` | ❌ Não existe | Referenciado no README | Criar arquivo |
| `LICENSE` | ❌ Não existe | MIT referenciado | Criar arquivo |

---

## Próximas Ações Prioritárias

1. **Criar documentação por domínio faltante** (`docs/pagamentos.md`, `docs/recebimentos.md`, `docs/pix-automatico.md`, `docs/conta-certa.md`, `docs/hub-contas.md`, `docs/med.md`)
2. **Criar documentação transversal** (`docs/exceptions.md`, `docs/middlewares.md`)
3. **Criar arquivos de governança** (`UPGRADE.md`, `CONTRIBUTING.md`, `LICENSE`)
3. **Atualizar `README.md`** com badges v1.7.0, link para novos docs, tabela de cobertura atualizada
4. **Atualizar `CHANGELOG.md`** — mover [Unreleased] para v1.7.0, adicionar seção [Unreleased] nova
5. **Verificar se `composer.json` version** está sincronizado com tag v1.7.0

---

## Breaking Changes Registrados

| Versão | Mudança | Tipo | UPGRADE.md |
|--------|---------|------|------------|
| v1.2.0 | Drop Laravel 11 e PHP 8.2 — requer Laravel 12/13, PHP 8.3+ | MAJOR | ❌ Pendente |
| v1.0.0 → v1.1.0 | DTOs readonly, WebhookController, CI | MINOR | N/A |

> ⚠️ **Regra**: breaking change na **API pública do pacote** (nomes de método, assinatura, namespace) = MAJOR. Mudança na API da Transfeera = ajuste interno (PATCH/MINOR conforme impacto).