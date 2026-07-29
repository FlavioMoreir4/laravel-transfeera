<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de instrução de pagamento (Payment Intent) Pix Automático.
 *
 * @see https://docs.transfeera.dev/reference/create-automatic-pix-payment-intent.md
 */
readonly class PaymentIntentDTO
{
    /**
     * @param  string  $authorizationId  ID da autorização Pix Automático
     * @param  int  $value  Valor em centavos
     * @param  string|null  $description  Descrição do pagamento
     * @param  string|null  $dueDate  Data de vencimento (Y-m-d)
     */
    public function __construct(
        public string $authorizationId,
        public int $value,
        public ?string $description = null,
        public ?string $dueDate = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'authorization_id' => $this->authorizationId,
            'value' => $this->value,
            'description' => $this->description,
            'due_date' => $this->dueDate,
        ], fn ($value) => $value !== null);
    }
}
