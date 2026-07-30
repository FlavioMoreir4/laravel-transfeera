<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de QR Code Pix cobrança imediata.
 *
 * @see https://docs.transfeera.dev/reference/post_pix-qrcode-collection-immediate.md
 */
readonly class PixQrCodeImmediateDTO
{
    /**
     * @param  string  $key  Chave Pix
     * @param  int  $value  Valor em centavos
     * @param  string|null  $description  Descrição
     * @param  string|null  $additionalData  Dados adicionais
     */
    public function __construct(
        public string $key,
        public int $value,
        public ?string $description = null,
        public ?string $additionalData = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'key' => $this->key,
            'value' => $this->value,
            'description' => $this->description,
            'additional_data' => $this->additionalData,
        ], fn ($value) => $value !== null);
    }
}
