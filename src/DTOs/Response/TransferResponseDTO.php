<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Transferência.
 *
 * @see https://docs.transfeera.dev/reference/get_transfer-id.md
 */
class TransferResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $batchId         ID do lote
     * @param  int     $amount          Valor em centavos
     * @param  string  $pixKey          Chave Pix do favorecido
     * @param  string|null $pixKeyType  Tipo da chave
     * @param  string|null $description Descrição
     * @param  string|null $createdAt   Data criação
     * @param  string|null $updatedAt   Data atualização
     */
    public function __construct(
        public string $batchId,
        public int $amount,
        public string $pixKey,
        public ?string $pixKeyType = null,
        public ?string $description = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
        parent::__construct('', '', $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'batch_id' => $this->batchId,
            'amount' => $this->amount,
            'pix_key' => $this->pixKey,
            'pix_key_type' => $this->pixKeyType,
            'description' => $this->description,
        ]), fn ($value) => $value !== null);
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            batchId: $data['batch_id'] ?? '',
            amount: $data['amount'] ?? 0,
            pixKey: $data['pix_key'] ?? '',
            pixKeyType: $data['pix_key_type'] ?? null,
            description: $data['description'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}