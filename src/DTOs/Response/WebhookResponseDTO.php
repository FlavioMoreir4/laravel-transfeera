<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para URLs de Webhook.
 *
 * @property-read string $id
 * @property-read string $url
 * @property-read array<string, mixed>|null $events
 * @property-read string|null $secret
 * @property-read string|null $created_at
 * @property-read string|null $updated_at
 */
class WebhookResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da URL
     * @param  string  $url  URL do webhook
     * @param  array<string, mixed>|null  $events  Eventos configurados
     * @param  string|null  $secret  Secret da URL
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        string $id,
        public string $url,
        public ?array $events = null,
        public ?string $secret = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ) {
        parent::__construct($id, '', $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'url' => $this->url,
            'events' => $this->events,
            'secret' => $this->secret,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            url: (string) ($data['url'] ?? ''),
            events: $data['events'] ?? null,
            secret: isset($data['secret']) ? (string) $data['secret'] : null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
