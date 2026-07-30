<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para pagamento gerado por recorrência.
 *
 * @see https://docs.transfeera.dev/reference/get_payout-recurrences-id-payments.md
 */
class RecurrencePaymentResponseDTO extends BaseResponseDTO
{
    /**
     * @param  int  $value  Valor em centavos
     * @param  string  $dueDate  Data de vencimento
     * @param  string  $status  Status: pending, registered, paid, cancelled
     * @param  string|null  $paidAt  Data pagamento
     * @param  int|null  $paidValue  Valor pago em centavos
     * @param  string|null  $paymentMethod  Método de pagamento
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public int $value,
        public string $dueDate,
        public string $status,
        public ?string $paidAt = null,
        public ?int $paidValue = null,
        public ?string $paymentMethod = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public string $id = '',
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'value' => $this->value,
            'due_date' => $this->dueDate,
            'paid_at' => $this->paidAt,
            'paid_value' => $this->paidValue,
            'payment_method' => $this->paymentMethod,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            value: $data['value'] ?? $data['valor'] ?? 0,
            dueDate: $data['due_date'] ?? $data['vencimento'] ?? '',
            status: $data['status'] ?? '',
            paidAt: $data['paid_at'] ?? $data['data_pagamento'] ?? null,
            paidValue: $data['paid_value'] ?? $data['valor_pago'] ?? null,
            paymentMethod: $data['payment_method'] ?? $data['metodo_pagamento'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
        );
    }
}
