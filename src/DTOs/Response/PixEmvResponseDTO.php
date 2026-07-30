<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para parsing de EMV Pix.
 *
 * Representa os dados extraídos de um código Pix Copia e Cola (EMV).
 *
 * @see https://docs.transfeera.dev/reference/post_pix-emv.md
 */
class PixEmvResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $key  Chave Pix
     * @param  int  $value  Valor em centavos
     * @param  string|null  $description  Descrição
     * @param  string|null  $city  Cidade do recebedor
     * @param  string|null  $merchantName  Nome do recebedor
     * @param  string|null  $merchantCity  Cidade do estabelecimento
     * @param  string|null  $txId  Transaction ID
     * @param  bool  $reusable  Se o QR Code é reutilizável
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $key,
        public int $value,
        public ?string $description = null,
        public ?string $city = null,
        public ?string $merchantName = null,
        public ?string $merchantCity = null,
        public ?string $txId = null,
        public bool $reusable = false,
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
            'key' => $this->key,
            'value' => $this->value,
            'description' => $this->description,
            'city' => $this->city,
            'merchant_name' => $this->merchantName,
            'merchant_city' => $this->merchantCity,
            'tx_id' => $this->txId,
            'reusable' => $this->reusable,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            key: $data['key'] ?? $data['chave'] ?? '',
            value: $data['value'] ?? $data['valor'] ?? 0,
            description: $data['description'] ?? $data['descricao'] ?? null,
            city: $data['city'] ?? $data['cidade'] ?? null,
            merchantName: $data['merchant_name'] ?? $data['nome_recebedor'] ?? null,
            merchantCity: $data['merchant_city'] ?? $data['cidade_recebedor'] ?? null,
            txId: $data['tx_id'] ?? $data['txid'] ?? null,
            reusable: $data['reusable'] ?? $data['reutilizavel'] ?? false,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
