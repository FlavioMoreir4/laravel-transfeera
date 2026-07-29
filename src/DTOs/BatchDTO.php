<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação e atualização de lotes de pagamentos.
 *
 * @see https://docs.transfeera.dev/reference/post_batch.md
 */
readonly class BatchDTO
{
    /**
     * @param  string  $name  Nome identificador do lote
     * @param  string|null  $type  Tipo do lote (ex.: 'immediate', 'scheduled')
     * @param  string|null  $scheduledDate  Data agendada (Y-m-d)
     */
    public function __construct(
        public string $name,
        public ?string $type = null,
        public ?string $scheduledDate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'type' => $this->type,
            'scheduled_date' => $this->scheduledDate,
        ], fn ($value) => $value !== null);
    }
}
