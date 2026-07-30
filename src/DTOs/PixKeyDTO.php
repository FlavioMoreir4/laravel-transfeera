<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de uma chave Pix.
 *
 * @see https://docs.transfeera.dev/reference/post_pix-key.md
 */
readonly class PixKeyDTO
{
    /**
     * @param  string  $type  Tipo da chave: cpf, cnpj, email, phone, evp
     * @param  string  $value  Valor da chave Pix
     */
    public function __construct(
        public string $type,
        public string $value,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }
}
