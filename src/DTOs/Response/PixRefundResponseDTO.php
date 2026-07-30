<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para devolução (refund) de Pix Cash-in.
 *
 * @see https://docs.transfeera.dev/reference/post_pix-cashin-end2endid-refund.md
 */
class PixRefundResponseDTO extends BaseResponseDTO
{
    /**
     * @param  int  $amount  Valor em centavos
     * @param  string  $endToEndId  End-to-end ID da devolução
     * @param  string|null  $description  Descrição
     * @param  string  $status  Status devolução: processing, completed, failed
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public int $amount,
        public string $endToEndId,
        public ?string $description = null,
        public string $status = '',
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public string $id = '',
    ) {
        parent::__construct($id, $this->status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'amount' => $this->amount,
            'end_to_end_id' => $this->endToEndId,
            'description' => $this->description,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            amount: $data['amount'] ?? $data['valor'] ?? 0,
            endToEndId: $data['end_to_end_id'] ?? $data['end2end_id'] ?? '',
            description: $data['description'] ?? $data['descricao'] ?? null,
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
        );
    }
}
