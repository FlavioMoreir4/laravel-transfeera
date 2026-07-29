<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de autorização Pix Automático.
 *
 * @see https://docs.transfeera.dev/reference/create-automatic-pix-authorization.md
 */
readonly class AuthorizationDTO
{
    /**
     * @param  string  $payerPixKey  Chave Pix do pagador
     * @param  int  $limitValue  Limite máximo de cobrança em centavos
     * @param  string|null  $startDate  Data de início (Y-m-d)
     * @param  string|null  $endDate  Data de término (Y-m-d)
     * @param  array<string, mixed>|null  $splitPayment  Configuração de split
     */
    public function __construct(
        public string $payerPixKey,
        public int $limitValue,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?array $splitPayment = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'payer_pix_key' => $this->payerPixKey,
            'limit_value' => $this->limitValue,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'split_payment' => $this->splitPayment,
        ], fn ($value) => $value !== null);
    }
}
