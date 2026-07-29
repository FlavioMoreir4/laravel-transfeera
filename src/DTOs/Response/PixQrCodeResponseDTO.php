<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para QR Code Pix.
 *
 * @see https://docs.transfeera.dev/reference/get_pix-qrcode-id.md
 */
class PixQrCodeResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id              ID do QR Code
     * @param  string  $status          Status: active, revoked, expired
     * @param  string  $key             Chave Pix associada
     * @param  string  $type            Tipo: static, immediate, due_date
     * @param  int|null $value          Valor em centavos
     * @param  string|null $dueDate     Data vencimento
     * @param  string|null $emv         EMV copia e cola
     * @param  string|null $imageUrl    URL da imagem
     * @param  string|null $createdAt   Data criação
     * @param  string|null $updatedAt   Data atualização
     */
    public function __construct(
        public string $key,
        public string $type,
        public ?int $value = null,
        public ?string $dueDate = null,
        public ?string $emv = null,
        public ?string $imageUrl = null,
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
            'value' => $this->value,
            'due_date' => $this->dueDate,
            'emv' => $this->emv,
            'image_url' => $this->imageUrl,
        ]), fn ($value) => $value !== null);
    }

    public static function fromResponse(array $data): self
    {
        return new self(
            key: $data['key'] ?? '',
            type: $data['type'] ?? '',
            value: $data['value'] ?? null,
            dueDate: $data['due_date'] ?? null,
            emv: $data['emv'] ?? null,
            imageUrl: $data['image_url'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}