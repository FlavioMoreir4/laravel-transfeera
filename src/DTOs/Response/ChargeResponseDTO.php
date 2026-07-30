<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Cobrança (Charge).
 *
 * @see https://docs.transfeera.dev/reference/get_charges-id.md
 */
class ChargeResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id              ID da cobrança
     * @param  string  $status          Status: pending, completed, canceled, expired
     * @param  string  $payerName       Nome do pagador
     * @param  int     $value           Valor em centavos
     * @param  string|null $payerDocument CPF/CNPJ
     * @param  string|null $dueDate     Data vencimento
     * @param  string|null $pixKey      Chave Pix (se for Pix)
     * @param  array<string, mixed>|null $receivables Recebíveis associados
     * @param  string|null $createdAt   Data criação
     * @param  string|null $updatedAt   Data atualização
     */
    public function __construct(
        public string $payerName,
        public int $value,
        public ?string $payerDocument = null,
        public ?string $dueDate = null,
        public ?string $pixKey = null,
        public ?array $receivables = null,
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
            'payer_name' => $this->payerName,
            'value' => $this->value,
            'payer_document' => $this->payerDocument,
            'due_date' => $this->dueDate,
            'pix_key' => $this->pixKey,
            'receivables' => $this->receivables,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            payerName: $data['payer_name'] ?? '',
            value: $data['value'] ?? 0,
            payerDocument: $data['payer_document'] ?? null,
            dueDate: $data['due_date'] ?? null,
            pixKey: $data['pix_key'] ?? null,
            receivables: $data['receivables'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}