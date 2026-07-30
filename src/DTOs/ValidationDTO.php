<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de validação de conta bancária (Conta Certa).
 *
 * @see https://docs.transfeera.dev/reference/post-validation.md
 */
readonly class ValidationDTO
{
    /**
     * @param  string  $bankCode  Código do banco (ex.: '341')
     * @param  string  $agency  Agência
     * @param  string  $account  Conta
     * @param  string  $document  CPF/CNPJ
     * @param  string  $accountType  Tipo: checking, savings, salary, payment
     * @param  string|null  $name  Nome do titular
     */
    public function __construct(
        public string $bankCode,
        public string $agency,
        public string $account,
        public string $document,
        public string $accountType,
        public ?string $name = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'bank_code' => $this->bankCode,
            'agency' => $this->agency,
            'account' => $this->account,
            'document' => $this->document,
            'account_type' => $this->accountType,
            'name' => $this->name,
        ], fn ($value) => $value !== null);
    }
}
