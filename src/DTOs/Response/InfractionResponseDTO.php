<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Infração MED.
 *
 * @see https://docs.transfeera.dev/reference/infractions
 */
class InfractionResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da infração
     * @param  string  $status  Status: open, analyzing, refunded, contested
     * @param  string  $infractionType  Tipo: refund_request, contestation
     * @param  int  $amount  Valor em centavos
     * @param  string  $payerName  Nome do pagador
     * @param  string  $payerDocument  CPF/CNPJ do pagador
     * @param  string  $endToEndId  End-to-end ID da transação
     * @param  string|null  $dueDate  Data de vencimento
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $infractionType,
        public int $amount,
        public string $payerName,
        public string $payerDocument,
        public string $endToEndId,
        public ?string $dueDate = null,
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
            'infraction_type' => $this->infractionType,
            'amount' => $this->amount,
            'payer_name' => $this->payerName,
            'payer_document' => $this->payerDocument,
            'end_to_end_id' => $this->endToEndId,
            'due_date' => $this->dueDate,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            infractionType: $data['infraction_type'] ?? $data['tipo_infracao'] ?? '',
            amount: $data['amount'] ?? $data['valor'] ?? 0,
            payerName: $data['payer_name'] ?? $data['pagador'] ?? '',
            payerDocument: $data['payer_document'] ?? $data['pagador_documento'] ?? '',
            endToEndId: $data['end_to_end_id'] ?? $data['end2end_id'] ?? '',
            dueDate: $data['due_date'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
