<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Chave Pix.
 *
 * @see https://docs.transfeera.dev/reference/get_pix-key-id.md
 */
class PixKeyResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da chave
     * @param  string  $status  Status: active, inactive, verification_pending
     * @param  string  $type  Tipo: cpf, cnpj, email, phone, evp
     * @param  string  $value  Valor da chave
     * @param  string|null  $claimedAt  Data de reivindicação
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $type,
        public string $value,
        public ?string $claimedAt = null,
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
            'type' => $this->type,
            'value' => $this->value,
            'claimed_at' => $this->claimedAt,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            type: $data['type'] ?? '',
            value: $data['value'] ?? '',
            claimedAt: $data['claimed_at'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
