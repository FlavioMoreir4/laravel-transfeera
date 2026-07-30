<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Boleto.
 *
 * @see https://docs.transfeera.dev/reference/get_billet-id.md
 */
class BilletResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do boleto
     * @param  string  $status  Status: pending, registered, paid, expired, cancelled
     * @param  string  $billetNumber  Nosso número
     * @param  string  $barcode  Código de barras
     * @param  int  $value  Valor em centavos
     * @param  string  $dueDate  Data de vencimento
     * @param  string  $beneficiaryName  Nome do beneficiário
     * @param  string  $beneficiaryDocument  CPF/CNPJ do beneficiário
     * @param  string|null  $paidAt  Data de pagamento
     * @param  int|null  $paidValue  Valor pago em centavos
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $billetNumber,
        public string $barcode,
        public int $value,
        public string $dueDate,
        public string $beneficiaryName,
        public string $beneficiaryDocument,
        public ?string $paidAt = null,
        public ?int $paidValue = null,
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
            'billet_number' => $this->billetNumber,
            'barcode' => $this->barcode,
            'value' => $this->value,
            'due_date' => $this->dueDate,
            'beneficiary_name' => $this->beneficiaryName,
            'beneficiary_document' => $this->beneficiaryDocument,
            'paid_at' => $this->paidAt,
            'paid_value' => $this->paidValue,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            billetNumber: $data['billet_number'] ?? $data['nosso_numero'] ?? '',
            barcode: $data['barcode'] ?? $data['codigo_barras'] ?? '',
            value: $data['value'] ?? $data['valor'] ?? 0,
            dueDate: $data['due_date'] ?? $data['vencimento'] ?? '',
            beneficiaryName: $data['beneficiary_name'] ?? $data['beneficiario'] ?? '',
            beneficiaryDocument: $data['beneficiary_document'] ?? $data['beneficiario_documento'] ?? '',
            paidAt: $data['paid_at'] ?? $data['data_pagamento'] ?? null,
            paidValue: $data['paid_value'] ?? $data['valor_pago'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
