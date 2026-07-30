<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Lote (Batch).
 *
 * @see https://docs.transfeera.dev/reference/get_batch-id.md
 */
class BatchResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do lote
     * @param  string  $status  Status: pending, processing, completed, failed, closed
     * @param  string  $name  Nome do lote
     * @param  string|null  $type  Tipo: immediate, scheduled
     * @param  string|null  $scheduledDate  Data agendada
     * @param  int|null  $totalTransfers  Total de transferências
     * @param  int|null  $totalValue  Valor total em centavos
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?string $scheduledDate = null,
        public ?int $totalTransfers = null,
        public ?int $totalValue = null,
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
            'name' => $this->name,
            'type' => $this->type,
            'scheduled_date' => $this->scheduledDate,
            'total_transfers' => $this->totalTransfers,
            'total_value' => $this->totalValue,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            name: $data['name'] ?? '',
            type: $data['type'] ?? null,
            scheduledDate: $data['scheduled_date'] ?? null,
            totalTransfers: $data['total_transfers'] ?? null,
            totalValue: $data['total_value'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
