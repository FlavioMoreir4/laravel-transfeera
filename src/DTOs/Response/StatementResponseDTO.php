<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Saldo e Extrato.
 *
 * @see https://docs.transfeera.dev/reference/statement
 */
class StatementResponseDTO extends BaseResponseDTO
{
    /**
     * @param  int  $balance  Saldo disponível em centavos
     * @param  int  $blocked  Saldo bloqueado em centavos
     * @param  int  $total  Saldo total em centavos
     * @param  string|null  $lastUpdated  Data da última atualização
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public int $balance,
        public int $blocked = 0,
        public int $total = 0,
        public ?string $lastUpdated = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public string $id = '',
        public string $status = '',
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'balance' => $this->balance,
            'blocked' => $this->blocked,
            'total' => $this->total,
            'last_updated' => $this->lastUpdated,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            balance: $data['balance'] ?? $data['saldo'] ?? 0,
            blocked: $data['blocked'] ?? $data['bloqueado'] ?? 0,
            total: $data['total'] ?? 0,
            lastUpdated: $data['last_updated'] ?? $data['ultima_atualizacao'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
