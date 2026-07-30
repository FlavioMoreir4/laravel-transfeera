<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;
use FlavioMoreir4\Transfeera\DTOs\Response\BaseResponseDTO;

/**
 * DTO de resposta para Autorização Pix Automático.
 *
 * @see https://docs.transfeera.dev/reference/get-automatic-pix-authorization.md
 */
class AuthorizationResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id              ID da autorização
     * @param  string  $status          Status: active, cancelled, expired
     * @param  string  $payerPixKey     Chave Pix do pagador
     * @param  int     $limitValue      Limite em centavos
     * @param  string|null $startDate   Data início
     * @param  string|null $endDate     Data fim
     * @param  array<string, mixed>|null $splitPayment Split de pagamento
     * @param  string|null $createdAt   Data criação
     * @param  string|null $updatedAt   Data atualização
     */
    public function __construct(
        public string $payerPixKey,
        public int $limitValue,
        public ?string $startDate = null,
        public ?string $endDate = null,
        public ?array $splitPayment = null,
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
            'payer_pix_key' => $this->payerPixKey,
            'limit_value' => $this->limitValue,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'split_payment' => $this->splitPayment,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            payerPixKey: $data['payer_pix_key'] ?? '',
            limitValue: $data['limit_value'] ?? 0,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            splitPayment: $data['split_payment'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}