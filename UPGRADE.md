# Guia de Migração — laravel-transfeera

Este documento descreve os passos necessários para migrar entre versões principais (MAJOR) do pacote.

> **Regra**: Breaking changes na **API pública do pacote** (nomes de método, assinatura, namespace) exigem bump MAJOR e entrada neste arquivo. Mudanças na API da Transfeera são ajustes internos (PATCH/MINOR).

---

## Timeline Planejada

```
v1.9.x ─── v1.10.x ─── v1.11.x ─── ... ─── v1.13.x ─── v2.0.0
│           │             │                     │            │
│ atual     @deprecated   Deprecação            Última       🎯
│ v1.9.0    em *Raw()     avançada              v1.x         2026 Q1
```

---

## v2.0.0 (Planejado ~2026 Q1)

> ⚠️ **Esta versão ainda não foi lançada.** As mudanças abaixo estão documentadas para planejamento. Deprecações começarão na v1.10.0 com 6+ meses de aviso.

### Breaking Changes

| Mudança | Impacto | Migração |
|---------|---------|----------|
| **Laravel 12 removido** | Requer Laravel 13+ | `composer require laravel/framework:^13.0` |
| **PHP 8.4+ obrigatório** | Requer PHP 8.4 | Atualize PHP |
| **Métodos `*Raw()` removidos** | `BaseResource::deleteRaw()` | Use métodos específicos do Resource |
| **Connector interno** | `Connector` não será mais público | Use `TransfeeraClient` ou Facade |
| **Retornos de API sem DTO** | Resources que retornam `array` passam a retornar DTO | Ajuste acesso a propriedades |

### Passo a Passo de Migração

#### 1. Atualizar Dependências

```bash
# v2.0 requer Laravel 13+ e PHP 8.4+
composer require flaviomoreir4/laravel-transfeera:^2.0 \
    laravel/framework:^13.0 \
    --update-with-dependencies
```

#### 2. Substituir Métodos `*Raw()`

**Antes (v1.x):**
```php
$response = $batch->deleteRaw('payments', '/batch/123');
```

**Depois (v2.0):**
```php
$response = Transfeera::batches()->delete('123');
```

#### 3. Acessar Propriedades de DTO em vez de Array

Para Resources que retornam DTO, acesse com `->`:

```php
// v1.x (array, funciona)
$batch = Transfeera::batches()->get('123');
echo $batch['id'];     // string 'batch_123'

// v2.0 (DTO)
$batch = Transfeera::batches()->get('123');
echo $batch->id;       // string 'batch_123'
```

#### 4. Tratamento de Erros

```php
use FlavioMoreir4\Transfeera\Exceptions\{
    TransfeeraException,
    TransfeeraValidationException,    // 422
    TransfeeraRateLimitException,     // 429
    PaymentException,
    // ... demais por domínio
};

try {
    $batch = Transfeera::batches()->create([...]);
} catch (TransfeeraValidationException $e) {
    // $e->getErrors() retorna array de erros de validação
} catch (TransfeeraRateLimitException $e) {
    $retryAfter = $e->getRetryAfter();
} catch (PaymentException $e) {
    // Erro específico de pagamentos
} catch (TransfeeraException $e) {
    // Fallback genérico
}
```

#### 5. Remover Acesso Direto ao Connector

**Antes (v1.x):**
```php
$connector = app(\FlavioMoreir4\Transfeera\Http\Connector::class);
$response = $connector->get('payments', '/batch/123');
```

**Depois (v2.0):**
```php
// Via Facade (recomendado)
$response = Transfeera::batches()->get('123');

// Ou via Client
$client = app(\FlavioMoreir4\Transfeera\TransfeeraClient::class);
$response = $client->batches()->get('123');
```

---

## v1.2.0 (Jul 2025) — Drop Laravel 11 / PHP 8.2

### Breaking Changes

| Mudança | Tipo | Migração |
|---------|------|----------|
| Requer **PHP 8.3+** (era 8.2) | MAJOR | Atualize PHP |
| Requer **Laravel 12/13** (era 11/12) | MAJOR | `composer require laravel/framework:^12.0\|\|^13.0` |

### Migração

```bash
# 1. Atualizar PHP
sudo apt update && sudo apt install php8.3 php8.3-{cli,mbstring,xml,curl,bcmath,zip}

# 2. Atualizar Laravel
composer require laravel/framework:^12.0 --update-with-dependencies

# 3. Atualizar pacote
composer require flaviomoreir4/laravel-transfeera:^1.2

# 4. Rodar testes
composer test
composer phpstan
composer rector
```

---

## Referência Rápida: Quando Bump MAJOR vs MINOR vs PATCH

| Cenário | Versão | Exemplo |
|---------|--------|---------|
| Remover método público | **MAJOR** | `deleteRaw()` removido |
| Mudar assinatura de método público | **MAJOR** | `create(array $data)` → `create(array\|DTO $data)` |
| Tornar classe interna | **MAJOR** | `Connector` deixa de ser público |
| Remover suporte a Laravel/PHP | **MAJOR** | Laravel 12 → Laravel 13+ |
| Adicionar método público | **MINOR** | novo Resource `duplicate()` |
| Adicionar parâmetro opcional | **MINOR** | `list(array $params = [], ?string $accountId = null)` |
| Corrigir bug sem mudar API | **PATCH** | Fix no TokenManager cache lock |
| Ajuste interno (refatoração) | **PATCH** | Renomear variável privada |
| Adicionar exception pública | **MINOR** | Nova `PaymentException` |
| Marcar método como `@deprecated` | **MINOR** | `deleteRaw()` com `@deprecated` |

---

## Checklist Pré-Release (Para Mantenedores)

- [ ] `CHANGELOG.md` atualizado com `[Unreleased]` → nova versão + data
- [ ] `UPGRADE.md` atualizado se houver breaking changes
- [ ] `composer.json` version sincronizado com tag
- [ ] `composer test` + `composer phpstan` + `composer rector` passando
- [ ] Tag criada: `git tag --no-sign vX.Y.Z -m "release: vX.Y.Z"` (usar `--no-sign` se GPG não configurado)
- [ ] Push tag: `git push origin vX.Y.Z`
- [ ] Packagist sincronizou (verificar webhook GitHub)

---

## Links Úteis

- [CHANGELOG.md](docs/changelog.md) — Histórico completo
- [docs/adr/006-never-break-compatibility.md](docs/adr/006-never-break-compatibility.md) — Decisão arquitetural sobre v2.0.0
- [docs/roadmap.md](docs/roadmap.md) — Planejamento de versões
- [Conventional Commits](https://www.conventionalcommits.org/pt-br/v1.0.0/)
- [SemVer PT-BR](https://semver.org/lang/pt-BR/)
