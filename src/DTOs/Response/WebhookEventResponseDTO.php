<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Eventos de Webhook.
 *
 * @property-read string $id
 * @property-read string $event
 * @property-read string $status
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
class WebhookEventResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do evento
     * @param  string  $event  Nome do evento
     * @param  string  $status  Status: sent, resent, failed
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        string $id,
        public string $event,
        string $status = '',
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'event' => $this->event,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            event: (string) ($data['event'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
