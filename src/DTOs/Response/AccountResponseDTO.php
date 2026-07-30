<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Conta Digital (Hub de Contas).
 *
 * @see https://docs.transfeera.dev/reference/accounts
 */
class AccountResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da conta
     * @param  string  $status  Status: active, inactive, closed
     * @param  string  $name  Nome da conta
     * @param  string  $document  CPF/CNPJ
     * @param  string  $email  E-mail
     * @param  string|null  $phone  Telefone
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $name,
        public string $document,
        public string $email,
        public ?string $phone = null,
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
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            name: $data['name'] ?? $data['nome'] ?? '',
            document: $data['document'] ?? $data['documento'] ?? '',
            email: $data['email'] ?? '',
            phone: $data['phone'] ?? $data['telefone'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
