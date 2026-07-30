<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Instrução de Pagamento (Payment Intent) Pix Automático.
 *
 * @see https://docs.transfeera.dev/reference/get-automatic-pix-payment-intent.md
 */
class PaymentIntentResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id                 ID da instrução
     * @param  string  $status             Status: pending, completed, cancelled, failed
     * @param  string  $authorizationId    ID da autorização
     * @param  int     $value              Valor em centavos
     * @param  string|null $description    Descrição
     * @param  string|null $dueDate        Data vencimento
     * @param  string|null $createdAt      Data criação
     * @param  string|null $updatedAt      Data atualização
     */
    public function __construct(
        public string $authorizationId,
        public int $value,
        public ?string $description = null,
        public ?string $dueDate = null,
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
            'authorization_id' => $this->authorizationId,
            'value' => $this->value,
            'description' => $this->description,
            'due_date' => $this->dueDate,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            authorizationId: $data['authorization_id'] ?? '',
            value: $data['value'] ?? 0,
            description: $data['description'] ?? null,
            dueDate: $data['due_date'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}