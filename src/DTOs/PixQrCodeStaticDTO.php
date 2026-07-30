<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de QR Code Pix estático.
 *
 * @see https://docs.transfeera.dev/reference/post_pix-qrcode-static.md
 */
readonly class PixQrCodeStaticDTO
{
    /**
     * @param  string  $key  Chave Pix (email, telefone, CPF, CNPJ, EVP)
     * @param  int|null  $value  Valor em centavos (opcional para estático)
     * @param  string|null  $description  Descrição
     * @param  string|null  $additionalData  Dados adicionais (ex.: reference)
     */
    public function __construct(
        public string $key,
        public ?int $value = null,
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
