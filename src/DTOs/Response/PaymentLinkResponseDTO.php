<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Link de Pagamento.
 *
 * @see https://docs.transfeera.dev/reference/get_payment-links-id.md
 */
class PaymentLinkResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id              ID do link
     * @param  string  $status          Status: active, inactive, expired
     * @param  string  $name            Nome do produto
     * @param  int     $value           Valor em centavos
     * @param  string|null $description Descrição
     * @param  string|null $url         URL do link
     * @param  string|null $expiresAt   Data expiração
     * @param  string|null $createdAt   Data criação
     * @param  string|null $updatedAt   Data atualização
     */
    public function __construct(
        public string $name,
        public int $value,
        public ?string $description = null,
        public ?string $url = null,
        public ?string $expiresAt = null,
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
            'value' => $this->value,
            'description' => $this->description,
            'url' => $this->url,
            'expires_at' => $this->expiresAt,
        ]), fn ($value) => $value !== null);
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            name: $data['name'] ?? '',
            value: $data['value'] ?? 0,
            description: $data['description'] ?? null,
            url: $data['url'] ?? null,
            expiresAt: $data['expires_at'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}