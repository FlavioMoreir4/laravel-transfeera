<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de conta digital (Hub de Contas).
 *
 * @see https://docs.transfeera.dev/reference/post-account.md
 */
readonly class AccountDTO
{
    /**
     * @param  string  $name        Nome da conta
     * @param  string  $document    CPF/CNPJ
     * @param  string  $email       Email
     * @param  string|null $phone   Telefone
     * @param  string|null $tradeName Nome fantasia
     */
    public function __construct(
        public string $name,
        public string $document,
        public string $email,
        public ?string $phone = null,
        public ?string $tradeName = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'document' => $this->document,
            'email' => $this->email,
            'phone' => $this->phone,
            'trade_name' => $this->tradeName,
        ], fn ($value) => $value !== null);
    }
}