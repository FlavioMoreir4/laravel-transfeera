# Guia de Migração — laravel-transfeera

Este documento descreve os passos necessários para migrar entre versões principais (MAJOR) do pacote.

> **Regra**: Breaking changes na **API pública do pacote** (nomes de método, assinatura, namespace) exigem bump MAJOR e entrada neste arquivo. Mudanças na API da Transfeera são ajustes internos (PATCH/MINOR).

---

## v2.0.0 (Próximo Major) — Planejado

> ⚠️ Esta versão **ainda não foi lançada**. O conteúdo abaixo reflete o planejamento atual e pode mudar.

### Breaking Changes Previstas

| Área | Mudança | Impacto | Migração |
|------|---------|---------|----------|
| **Resources** | Todos retornam DTOs tipados (não arrays) | Código que acessa `$result['field']` falha | Substitua `$result['field']` por `$result->field` |
| **Exceptions** | Removido fallback para `TransfeeraException` em erros de domínio | `catch (TransfeeraException)` não pega `PaymentException` etc. | Adicione `catch (PaymentException\|ReceivableException\|...)` antes do `catch (TransfeeraException)` |
| **DTOs Request** | Obrigatório usar DTOs (não arrays) em `create()`, `update()` | Código passando arrays falha no type hint | Use `new BatchDTO([...])` ou `$dto->toArray()` |
| **BaseResource** | Métodos `*Raw()` removidos | Código chamando `getRaw()`, `postRaw()` falha | Use `getDTO()`, `postDTO()` ou `get()` / `post()` |
| **Connector** | Métodos `get()`, `post()`, etc. removidos da API pública | Código injetando `Connector` diretamente impactado | Use `TransfeeraClient` ou Facade |

---

### Passo a Passo de Migração (Quando v2.0.0 for lançada)

#### 1. Atualizar Dependências

```bash
composer require flaviomoreir4/laravel-transfeera:^2.0
```

#### 2. Substituir Acesso a Arrays por Propriedades de DTO

**Antes (v1.x):**
```php
$batch = Transfeera::batches()->create(['name' => 'Lote']);
echo $batch['id'];
echo $batch['status'];

$transfers = Transfeera::transfers()->list('batch_123');
foreach ($transfers as $t) {
    echo $t['amount'];
}
```

**Depois (v2.0):**
```php
$batch = Transfeera::batches()->create(new BatchDTO(name: 'Lote'));
echo $batch->id;
echo $batch->status;

$transfers = Transfeera::transfers()->list('batch_123');
foreach ($transfers as $t) {
    echo $t->amount;
}
```

#### 3. Atualizar Try/Catch de Exceptions

**Antes (v1.x):**
```php
try {
    Transfeera::batches()->create([...]);
} catch (TransfeeraException $e) {
    // Capturava tudo
}
```

**Depois (v2.0):**
```php
use FlavioMoreir4\Transfeera\Exceptions\{
    PaymentException,
    ReceivableException,
    PixAutomaticoException,
    ContaCertaException,
    AccountException,
    InfractionException,
    TransfeeraAuthenticationException,
    TransfeeraValidationException,
    TransfeeraRateLimitException,
    TransfeeraException
};

try {
    Transfeera::batches()->create(new BatchDTO(...));
} catch (TransfeeraValidationException $e) {
    // 422
    return response()->json(['errors' => $e->getErrors()], 422);
} catch (TransfeeraAuthenticationException $e) {
    // 401
    return response()->json(['error' => 'unauthorized'], 401);
} catch (TransfeeraRateLimitException $e) {
    // 429
    return response()->json(['error' => 'rate_limit'], 429)
        ->header('Retry-After', $e->getRetryAfter() ?? 60);
} catch (PaymentException $e) {
    // Erros da API de pagamentos (outros códigos)
    return response()->json(['error' => $e->getMessage()], $e->getStatusCode() ?: 500);
} catch (TransfeeraException $e) {
    // Fallback genérico
    return response()->json(['error' => 'transfeera_error'], 500);
}
```

#### 4. Substituir Arrays por DTOs em Create/Update

**Antes (v1.x):**
```php
Transfeera::batches()->create(['name' => 'Lote', 'type' => 'scheduled', 'scheduled_date' => '2025-01-15']);
Transfeera::transfers()->create('batch_123', ['amount' => 15000, 'pix_key' => 'a@b.com']);
```

**Depois (v2.0):**
```php
use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;

Transfeera::batches()->create(new BatchDTO(
    name: 'Lote',
    type: 'scheduled',
    scheduledDate: '2025-01-15',
));

Transfeera::transfers()->create('batch_123', new TransferDTO(
    amount: 15000,
    pixKey: 'a@b.com',
    pixKeyType: 'email',
));
```

> **Dica**: Se precisar migrar gradualmente, use `$dto->toArray()`:
> ```php
> $batch = Transfeera::batches()->create(new BatchDTO(...)->toArray());
> ```

#### 5. Remover Uso de Métodos `*Raw()`

**Antes (v1.x):**
```php
$connector = app(\FlavioMoreir4\Transfeera\Http\Connector::class);
$response = $connector->getRaw(Connector::DOMAIN_PAYMENTS, '/batch/123');
$data = $connector->postRaw(Connector::DOMAIN_PAYMENTS, '/batch', ['name' => 'X']);
```

**Depois (v2.0):**
```php
// Via Facade (recomendado)
$batch = Transfeera::batches()->get('123');
$batch = Transfeera::batches()->create(new BatchDTO(name: 'X'));

// Ou via Client
$client = app(\FlavioMoreir4\Transfeera\TransfeeraClient::class);
$batch = $client->batches()->get('123');
```

#### 6. Atualizar Configuração (se aplicável)

```php
// config/transfeera.php — v2.0 pode adicionar novas chaves
// Verifique o arquivo publicado após `php artisan vendor:publish --tag=transfeera-config`
```

---

## v1.2.0 (Jul 2025) — Drop Laravel 11 / PHP 8.2

### Breaking Changes

| Mudança | Tipo | Migração |
|---------|------|----------|
| Requer **PHP 8.3+** (era 8.2) | MAJOR | Atualize PHP: `sudo apt upgrade php` ou `brew upgrade php` |
| Requer **Laravel 12/13** (era 11/12) | MAJOR | `composer require laravel/framework:^12.0\|^13.0` |
| Rector só roda no PHP 8.3 | INTERNO | CI roda Rector só no PHP 8.3 |

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

## v1.0.0 → v1.1.0 (Jul 2025)

### Mudanças (MINOR — Retrocompatível)

| Feature | Descrição |
|---------|-----------|
| **DTOs readonly** | `BatchDTO`, `TransferDTO`, `PixKeyDTO`, `ChargeDTO`, `AuthorizationDTO`, `PaymentIntentDTO` |
| **Webhooks prontos** | `WebhookController`, rotas, `SignatureValidator`, `TransfeeraWebhookReceived` event, `LogTransfeeraWebhook` listener |
| **CI/CD** | GitHub Actions matrix PHP 8.2/8.3/8.4 × Laravel 11/12 |
| **Badges** | CI, Tests, PHPStan, Rector no README |

### Migração (Opcional)

```php
// Antes: arrays
Transfeera::batches()->create(['name' => 'Lote']);

// Depois: DTOs (recomendado, arrays ainda funcionam)
use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
Transfeera::batches()->create(new BatchDTO(name: 'Lote'));
```

---

## Referência Rápida: Quando Bump MAJOR vs MINOR vs PATCH

| Cenário | Versão | Exemplo |
|---------|--------|---------|
| Remover método público | **MAJOR** | `BatchResource::getRaw()` removido |
| Mudar assinatura de método público | **MAJOR** | `create(array $data)` → `create(BatchDTO $dto)` |
| Renomear classe/namespace público | **MAJOR** | `TransfeeraClient` → `TransfeeraSDK` |
| Adicionar método público | **MINOR** | `BatchResource::duplicate()` novo |
| Adicionar parâmetro opcional | **MINOR** | `list(array $params = [])` → `list(array $params = [], ?string $accountId = null)` |
| Corrigir bug sem mudar API | **PATCH** | Fix no `TokenManager` cache lock |
| Ajuste interno (refatoração) | **PATCH** | Renomear variável privada |
| Nova exception pública | **MINOR** | `PaymentException` nova |
| Remover exception pública | **MAJOR** | `TransfeeraException` não pega mais `PaymentException` |

---

## Checklist Pré-Release (Para Mantenedores)

- [ ] `CHANGELOG.md` atualizado com `[Unreleased]` → nova versão + data
- [ ] `UPGRADE.md` atualizado se houver breaking changes
- [ ] `composer.json` version sincronizado com tag
- [ ] `php artisan vendor:publish --tag=transfeera-config` testado
- [ ] `composer test` + `composer phpstan` + `composer rector` passando
- [ ] Tag criada: `git tag -s vX.Y.Z -m "release: vX.Y.Z - ..."`
- [ ] Push tag: `git push origin vX.Y.Z`
- [ ] Packagist sincronizou (verificar webhook GitHub)
- [ ] Anunciar release (GitHub Releases, changelog link)

---

## Links Úteis

- [CHANGELOG.md](CHANGELOG.md) — Histórico completo
- [CHANGELOG.md (docs/)](docs/changelog.md) — Versão amigável
- [Conventional Commits](https://www.conventionalcommits.org/pt-br/v1.0.0/) — Padrão de commits
- [SemVer PT-BR](https://semver.org/lang/pt-BR/) — Versionamento semântico