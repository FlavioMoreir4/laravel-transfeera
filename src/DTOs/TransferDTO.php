<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de uma transferência dentro de um lote.
 *
 * @see https://docs.transfeera.dev/reference/post_batch-id-transfer.md
 */
readonly class TransferDTO
{
    /**
     * @param  int  $amount  Valor em centavos
     * @param  string  $pixKey  Chave Pix do favorecido
     * @param  string|null  $pixKeyType  Tipo da chave Pix (cpf, cnpj, email, phone, evp)
     * @param  string|null  $description  Descrição do pagamento
     */
    public function __construct(
        public int $amount,
        public string $pixKey,
        public ?string $pixKeyType = null,
        public ?string $description = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'amount' => $this->amount,
            'pix_key' => $this->pixKey,
            'pix_key_type' => $this->pixKeyType,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}
