<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Pix recebido (Cash-in).
 *
 * @see https://docs.transfeera.dev/reference/get_pix-cashin-end2endid.md
 */
class PixCashInResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do cash-in (end2endId)
     * @param  string  $status  Status: completed, returned
     * @param  int  $value  Valor em centavos
     * @param  string  $payerName  Nome do pagador
     * @param  string  $payerDocument  CPF/CNPJ do pagador
     * @param  string  $payerPixKey  Chave Pix do pagador
     * @param  string  $receiverPixKey  Chave Pix do recebedor
     * @param  string|null  $description  Descrição
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public int $value,
        public string $payerName,
        public string $payerDocument,
        public string $payerPixKey,
        public string $receiverPixKey,
        public ?string $description = null,
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
            'value' => $this->value,
            'payer_name' => $this->payerName,
            'payer_document' => $this->payerDocument,
            'payer_pix_key' => $this->payerPixKey,
            'receiver_pix_key' => $this->receiverPixKey,
            'description' => $this->description,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            value: $data['value'] ?? 0,
            payerName: $data['payer_name'] ?? '',
            payerDocument: $data['payer_document'] ?? '',
            payerPixKey: $data['payer_pix_key'] ?? '',
            receiverPixKey: $data['receiver_pix_key'] ?? '',
            description: $data['description'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['end2end_id'] ?? $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
