<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de cobrança (boleto e/ou Pix).
 *
 * @see https://docs.transfeera.dev/reference/post_charges.md
 */
readonly class ChargeDTO
{
    /**
     * @param  string  $payerName  Nome do pagador
     * @param  int  $value  Valor em centavos
     * @param  string|null  $payerDocument  CPF/CNPJ do pagador
     * @param  string|null  $dueDate  Data de vencimento (Y-m-d)
     * @param  array<string, mixed>|null  $metadata  Metadados adicionais
     */
    public function __construct(
        public string $payerName,
        public int $value,
        public ?string $payerDocument = null,
        public ?string $dueDate = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'payer_name' => $this->payerName,
            'value' => $this->value,
            'payer_document' => $this->payerDocument,
            'due_date' => $this->dueDate,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
