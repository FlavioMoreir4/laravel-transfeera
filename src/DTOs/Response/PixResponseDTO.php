<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para consulta de chave Pix (DICT) e parser EMV.
 *
 * @see https://docs.transfeera.dev/reference/pix
 */
class PixResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $key  Chave Pix consultada
     * @param  string  $type  Tipo: cpf, cnpj, email, phone, evp
     * @param  string  $name  Nome do recebedor
     * @param  string  $document  CPF/CNPJ do recebedor
     * @param  string  $bankCode  Código do banco (ISPB)
     * @param  string  $bankName  Nome do banco
     * @param  string  $agency  Agência
     * @param  string  $account  Conta
     * @param  string  $accountType  Tipo: checking, savings
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $key,
        public string $type,
        public string $name,
        public string $document,
        public string $bankCode,
        public string $bankName,
        public string $agency,
        public string $account,
        public string $accountType,
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
            'key' => $this->key,
            'type' => $this->type,
            'name' => $this->name,
            'document' => $this->document,
            'bank_code' => $this->bankCode,
            'bank_name' => $this->bankName,
            'agency' => $this->agency,
            'account' => $this->account,
            'account_type' => $this->accountType,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            key: $data['key'] ?? $data['chave'] ?? '',
            type: $data['type'] ?? $data['tipo'] ?? '',
            name: $data['name'] ?? $data['nome'] ?? '',
            document: $data['document'] ?? $data['documento'] ?? '',
            bankCode: $data['bank_code'] ?? $data['ispb'] ?? '',
            bankName: $data['bank_name'] ?? $data['banco'] ?? '',
            agency: $data['agency'] ?? $data['agencia'] ?? '',
            account: $data['account'] ?? $data['conta'] ?? '',
            accountType: $data['account_type'] ?? $data['tipo_conta'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
