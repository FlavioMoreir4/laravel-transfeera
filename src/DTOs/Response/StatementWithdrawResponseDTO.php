<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Saque/Resgate de saldo.
 *
 * @see https://docs.transfeera.dev/reference/withdraw
 */
class StatementWithdrawResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do saque
     * @param  string  $status  Status: processing, completed, failed
     * @param  int  $amount  Valor em centavos
     * @param  string  $pixKey  Chave Pix de destino
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        string $id,
        string $status,
        public int $amount,
        public string $pixKey = '',
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'amount' => $this->amount,
            'pix_key' => $this->pixKey,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            amount: (int) ($data['amount'] ?? 0),
            pixKey: (string) ($data['pix_key'] ?? ''),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
