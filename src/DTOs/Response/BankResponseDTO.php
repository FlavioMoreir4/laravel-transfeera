<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Banco (Pagamentos).
 *
 * @see https://docs.transfeera.dev/reference/get_bank.md
 */
class BankResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id          ID do banco
     * @param  string  $name        Nome do banco
     * @param  string  $code        Código do banco (ISPB)
     * @param  string  $status      Status
     * @param  string|null $createdAt Data criação
     * @param  string|null $updatedAt Data atualização
     */
    public function __construct(
        public string $name,
        public string $code,
        public string $id = '',
        public string $status = '',
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'name' => $this->name,
            'code' => $this->code,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            name: $data['name'] ?? '',
            code: $data['code'] ?? $data['ispb'] ?? '',
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}