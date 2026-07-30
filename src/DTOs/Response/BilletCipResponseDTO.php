<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para consulta de situação de boleto na CIP.
 *
 * @see https://docs.transfeera.dev/reference/get_billet-consult.md
 */
class BilletCipResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $billetNumber  Nosso número
     * @param  int  $value  Valor em centavos
     * @param  string  $dueDate  Data de vencimento
     * @param  string  $cipStatus  Situação na CIP
     * @param  string|null  $cpcStatus  CPC status (se aplicável)
     * @param  string|null  $beneficiaryName  Nome do beneficiário
     * @param  string|null  $beneficiaryDocument  CPF/CNPJ do beneficiário
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $billetNumber,
        public int $value,
        public string $dueDate,
        public string $cipStatus,
        public ?string $cpcStatus = null,
        public ?string $beneficiaryName = null,
        public ?string $beneficiaryDocument = null,
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
            'value' => $this->value,
            'due_date' => $this->dueDate,
            'cip_status' => $this->cipStatus,
            'cpc_status' => $this->cpcStatus,
            'beneficiary_name' => $this->beneficiaryName,
            'beneficiary_document' => $this->beneficiaryDocument,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            billetNumber: $data['billet_number'] ?? $data['nosso_numero'] ?? '',
            value: $data['value'] ?? $data['valor'] ?? 0,
            dueDate: $data['due_date'] ?? $data['vencimento'] ?? '',
            cipStatus: $data['cip_status'] ?? $data['situacao_cip'] ?? '',
            cpcStatus: $data['cpc_status'] ?? $data['situacao_cpc'] ?? null,
            beneficiaryName: $data['beneficiary_name'] ?? $data['beneficiario'] ?? null,
            beneficiaryDocument: $data['beneficiary_document'] ?? $data['beneficiario_documento'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
