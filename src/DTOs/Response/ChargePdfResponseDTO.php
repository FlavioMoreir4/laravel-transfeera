<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para download de comprovante PDF de cobrança.
 *
 * @see https://docs.transfeera.dev/reference/charges
 */
class ChargePdfResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $url  URL temporária para download do PDF
     * @param  string|null  $contentType  Content-Type do arquivo (ex: application/pdf)
     * @param  int|null  $size  Tamanho em bytes
     * @param  string|null  $expiresAt  Data de expiração da URL
     * @param  string  $id  ID da cobrança
     * @param  string  $status  Status do comprovante
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $url,
        public ?string $contentType = null,
        public ?int $size = null,
        public ?string $expiresAt = null,
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
            'url' => $this->url,
            'content_type' => $this->contentType,
            'size' => $this->size,
            'expires_at' => $this->expiresAt,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            url: $data['url'] ?? '',
            contentType: $data['content_type'] ?? $data['contentType'] ?? null,
            size: $data['size'] ?? null,
            expiresAt: $data['expires_at'] ?? $data['expiresAt'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
