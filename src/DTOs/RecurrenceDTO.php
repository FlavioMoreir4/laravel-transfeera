<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de recorrência de pagamentos (payouts).
 *
 * @see https://docs.transfeera.dev/reference/post_payout_recurrences.md
 */
readonly class RecurrenceDTO
{
    /**
     * @param  string  $name             Nome da recorrência
     * @param  int     $value            Valor em centavos
     * @param  string  $pixKey           Chave Pix do favorecido
     * @param  string  $pixKeyType       Tipo da chave Pix
     * @param  string  $startDate        Data de início (Y-m-d)
     * @param  string  $frequency        Frequência: daily, weekly, monthly, yearly
     * @param  int     $interval         Intervalo entre pagamentos
     * @param  string|null $endDate         Data fim (Y-m-d) - opcional
     * @param  string|null $description  Descrição
     * @param  array<string, mixed>|null $metadata Metadados
     */
    public function __construct(
        public string $name,
        public int $value,
        public string $pixKey,
        public string $pixKeyType,
        public string $startDate,
        public string $frequency,
        public int $interval = 1,
        public ?string $endDate = null,
        public ?string $description = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'value' => $this->value,
            'pix_key' => $this->pixKey,
            'pix_key_type' => $this->pixKeyType,
            'start_date' => $this->startDate,
            'frequency' => $this->frequency,
            'interval' => $this->interval,
            'end_date' => $this->endDate,
            'description' => $this->description,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}