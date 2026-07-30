# Contribuindo — laravel-transfeera

Obrigado por contribuir! Este guia explica o fluxo de trabalho, padrões de código e requisitos para PRs.

---

## Como Contribuir

### 1. Preparação

```bash
# Fork + clone
git clone https://github.com/seu-usuario/laravel-transfeera.git
cd laravel-transfeera

# Instalar dependências
composer install

# Configurar ambiente de teste
cp .env.example .env.testing
# Editar .env.testing com credenciais sandbox se quiser testes de integração
```

### 2. Branch

```bash
git checkout -b feat/nova-funcionalidade
# ou
git checkout -b fix/correcao-bug
```

> Use o padrão: `<tipo>/<descrição-curta>` (ex: `feat/pix-automatico-webhooks`, `fix/token-expired-cache`)

### 3. Desenvolvimento

- Escreva o teste primeiro (TDD leve)
- Implemente a funcionalidade
- Rode os verificadores:

```bash
composer test          # Pest - todos os testes
composer phpstan       # PHPStan nível 8
composer rector        # Rector dry-run
composer format        # Pint/PSR-12
```

### 4. Commit

Use **Conventional Commits**:

```bash
git add .
git commit -m "feat(pix): add webhook retry endpoint

- Add resendRetry() to PaymentIntentResource
- Add tests for retry flow
- Update pix-automatico.md docs

Closes #123"
```

> Tipos: `feat`, `fix`, `chore`, `ci`, `docs`, `refactor`, `style`, `test`
> Breaking change: adicione `!` após o tipo e `BREAKING CHANGE` no rodapé

### 5. Push + PR

```bash
git push origin feat/nova-funcionalidade
```

Abra o PR no GitHub preenchendo o template.

---

## Padrões de Código

### PHP

- **PHP 8.3+**, **strict_types=1**
- **readonly classes** para DTOs
- **Type hints** completos (params + return)
- **PHPDoc** em métodos públicos
- **Native enums** quando aplicável (PHP 8.1+)

### DTOs (Request/Response)

```php
// Request DTO - readonly, validação no constructor se necessário
readonly class TransferDTO
{
    public function __construct(
        public int $amount,
        public string $pixKey,
        public string $pixKeyType,
        public ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'pix_key' => $this->pixKey,
            'pix_key_type' => $this->pixKeyType,
            'description' => $this->description,
        ], fn ($v) => $v !== null);
    }
}

// Response DTO - fromResponse() factory
readonly class TransferResponseDTO extends BaseResponseDTO
{
    public function __construct(
        public string $batchId,
        public int $amount,
        public string $pixKey,
        public ?string $pixKeyType = null,
        public ?string $description = null,
        string $id = '',
        string $status = '',
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            batchId: $data['batch_id'] ?? '',
            amount: $data['amount'] ?? 0,
            pixKey: $data['pix_key'] ?? '',
            pixKeyType: $data['pix_key_type'] ?? null,
            description: $data['description'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
```

### Resources

- Herdam de `BaseResource`
- Usam métodos tipados: `getDTO()`, `getDTOList()`, `postDTO()`, `putDTO()`, `patchDTO()`
- Retornam DTOs de Response, não arrays

### Exceptions

- Hierarquia clara: `TransfeeraException` base → específicas por domínio
- Factory `fromResponse(array $payload, int $status)` nas exceptions de domínio

### Testes

- **Pest 4** com `Http::fake()` e fixtures reais
- Cobertura mínima: 90% Resources, 100% TokenManager/Exceptions
- Fixtures em `tests/Fixtures/` extraídas da doc oficial

```php
test('cria transferência', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/transfer' => Http::response([
            'id' => 'transfer_1',
            'batch_id' => 'batch_123',
            'amount' => 15000,
            'pix_key' => 'a@b.com',
            'status' => 'pending',
        ], 201),
    ]);

    $result = Transfeera::transfers()->create('batch_123', [
        'amount' => 15000,
        'pix_key' => 'a@b.com',
    ]);

    expect($result->id)->toBe('transfer_1');
});
```

### Documentação

- Atualize `docs/<dominio>.md` quando adicionar/alterar recurso
- Exemplos extraídos de testes reais
- Seção "Roadmap" para features documentadas mas não implementadas

---

## CI/CD

### GitHub Actions

- **Test Matrix**: PHP 8.3/8.4 × Laravel 12/13
- **Scripts**: `test`, `analyse`, `rector`, `format`
- **Badges**: CI, Tests, PHPStan, Rector no README

### Versionamento

- **SemVer estrito**: MAJOR = breaking API pública do pacote
- Tags: `vMAJOR.MINOR.PATCH` (assinadas GPG)
- CHANGELOG: Keep a Changelog (pt-BR)

---

## Regras de Ouro

1. **Não inventar payloads** — sempre consultar https://docs.transfeera.dev/llms.txt
2. **Minimalismo de dependências** — usar `illuminate/*`, `Http::`, `Cache::`, `Str::`
3. **Segurança first** — nunca commitar secrets, validar assinatura webhook com `hash_equals`
4. **Documentação viva** — código e docs mudam juntos
5. **Qualidade não negociável** — CI verde = obrigatório

---

## Dúvidas?

- Abra uma **Issue** para bugs/features
- Abra um **Discussion** para dúvidas de design/arquitetura
- Consulte [AGENTS.md](AGENTS.md) para instruções detalhadas de agentes IA