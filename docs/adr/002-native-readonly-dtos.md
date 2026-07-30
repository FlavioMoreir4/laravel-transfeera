# ADR-002: DTOs Nativos `readonly class` sem Bibliotecas

- **Status:** ✅ Aceito
- **Data:** 2025-07-30

## Contexto

O SDK precisa representar dados de entrada (requests) e saída (responses) da API de forma tipada e imutável. Bibliotecas como `spatie/laravel-data` oferecem mapeamento automático, validação e serialização, mas violam a política de zero dependências (ADR-001).

## Decisão

Usar **`readonly class` nativas do PHP 8.3+** para todos os DTOs:

### Request DTOs
```php
readonly class TransferDTO
{
    public function __construct(
        public int $amount,              // centavos
        public string $pixKey,
        public ?string $pixKeyType = null,
        public ?string $description = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'pix_key' => $this->pixKey,
            'pix_key_type' => $this->pixKeyType,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
```

### Response DTOs
```php
abstract readonly class BaseResponseDTO
{
    public function __construct(
        public string $id,
        public string $status,
        public ?string $createdAt = null,
    ) {}
}

readonly class BatchResponseDTO extends BaseResponseDTO
{
    public function __construct(
        public string $name,
        public string $type,
        string $id,
        string $status,
        ?string $createdAt = null,
    ) {
        parent::__construct($id, $status, $createdAt);
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            name: $data['name'],
            type: $data['type'],
            id: $data['id'],
            status: $data['status'],
            createdAt: $data['created_at'] ?? null,
        );
    }
}
```

## Consequências

**Positivas:**
- Zero dependências — alinhado com ADR-001
- Imutabilidade garantida pelo `readonly`
- Type hints nativos para PHPStan
- Sem magic methods — explícito e rastreável
- Serialização manual (toArray/fromResponse) dá controle total

**Negativas:**
- Mais boilerplate que bibliotecas de mapeamento automático
- Sem validação automática (deve ser feita manualmente ou no Resource)
- `fromResponse()` precisa ser mantido manualmente ao adicionar campos
- Herança com `readonly` exige repetir parâmetros do parent no construtor do filho

## Alternativas Consideradas

1. **spatie/laravel-data** — Rejeitado por violar ADR-001.
2. **Array puro (sem DTOs)** — Rejeitado. Perda total de type safety e IDE support.
3. **PHP 8.1+ Enums para campos fixos** — Aceito parcialmente. Usamos Enums para BatchStatus, ChargeStatus, etc.
