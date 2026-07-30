<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Validação Conta Certa.
 *
 * @see https://docs.transfeera.dev/reference/validations
 */
class ValidationResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da validação
     * @param  string  $status  Status: pending, processing, completed, failed
     * @param  string  $bankCode  Código do banco
     * @param  string  $agency  Agência
     * @param  string  $account  Conta
     * @param  string  $document  CPF/CNPJ
     * @param  string  $accountType  Tipo: checking, savings, salary
     * @param  string|null  $result  Resultado: match, mismatch, not_found
     * @param  string|null  $accountHolderName  Nome do titular (retornado se match)
     * @param  int|null  $score  Pontuação de match (0-100)
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $bankCode,
        public string $agency,
        public string $account,
        public string $document,
        public string $accountType,
        public ?string $result = null,
        public ?string $accountHolderName = null,
        public ?int $score = null,
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
            'bank_code' => $this->bankCode,
            'agency' => $this->agency,
            'account' => $this->account,
            'document' => $this->document,
            'account_type' => $this->accountType,
            'result' => $this->result,
            'account_holder_name' => $this->accountHolderName,
            'score' => $this->score,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            bankCode: $data['bank_code'] ?? $data['codigo_banco'] ?? '',
            agency: $data['agency'] ?? $data['agencia'] ?? '',
            account: $data['account'] ?? $data['conta'] ?? '',
            document: $data['document'] ?? $data['documento'] ?? '',
            accountType: $data['account_type'] ?? $data['tipo_conta'] ?? '',
            result: $data['result'] ?? $data['resultado'] ?? null,
            accountHolderName: $data['account_holder_name'] ?? $data['titular'] ?? null,
            score: $data['score'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
