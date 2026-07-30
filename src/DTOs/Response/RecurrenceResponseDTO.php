<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Recorrência de Pagamentos.
 *
 * @see https://docs.transfeera.dev/reference/recurrences
 */
class RecurrenceResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID da recorrência
     * @param  string  $status  Status: active, paused, cancelled, completed
     * @param  string  $name  Nome da recorrência
     * @param  string  $frequency  Frequência: weekly, biweekly, monthly, bimonthly, quarterly, semiannual, yearly
     * @param  int  $value  Valor em centavos
     * @param  string  $pixKey  Chave Pix do recebedor
     * @param  string|null  $description  Descrição
     * @param  string|null  $nextPayment  Data do próximo pagamento
     * @param  int|null  $totalPayments  Total de pagamentos previstos
     * @param  int|null  $executedPayments  Pagamentos executados
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $name,
        public string $frequency,
        public int $value,
        public string $pixKey,
        public ?string $description = null,
        public ?string $nextPayment = null,
        public ?int $totalPayments = null,
        public ?int $executedPayments = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public string $id = '',
        public string $status = '',
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'name' => $this->name,
            'frequency' => $this->frequency,
            'value' => $this->value,
            'pix_key' => $this->pixKey,
            'description' => $this->description,
            'next_payment' => $this->nextPayment,
            'total_payments' => $this->totalPayments,
            'executed_payments' => $this->executedPayments,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            name: $data['name'] ?? $data['nome'] ?? '',
            frequency: $data['frequency'] ?? $data['frequencia'] ?? '',
            value: $data['value'] ?? $data['valor'] ?? 0,
            pixKey: $data['pix_key'] ?? $data['chave_pix'] ?? '',
            description: $data['description'] ?? $data['descricao'] ?? null,
            nextPayment: $data['next_payment'] ?? $data['proximo_pagamento'] ?? null,
            totalPayments: $data['total_payments'] ?? $data['total_pagamentos'] ?? null,
            executedPayments: $data['executed_payments'] ?? $data['pagamentos_executados'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
