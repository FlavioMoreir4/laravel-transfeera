<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para criação de link de pagamento.
 *
 * @see https://docs.transfeera.dev/reference/post_payment-links.md
 */
readonly class PaymentLinkDTO
{
    /**
     * @param  string  $name  Nome do produto/serviço
     * @param  int  $value  Valor em centavos
     * @param  string|null  $description  Descrição
     * @param  int|null  $expiresIn  Expiração em dias
     * @param  string|null  $redirectUrl  URL de redirecionamento após pagamento
     * @param  array<string, mixed>|null  $metadata  Metadados
     */
    public function __construct(
        public string $name,
        public int $value,
        public ?string $description = null,
        public ?int $expiresIn = null,
        public ?string $redirectUrl = null,
        public ?array $metadata = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name' => $this->name,
            'value' => $this->value,
            'description' => $this->description,
            'expires_in' => $this->expiresIn,
            'redirect_url' => $this->redirectUrl,
            'metadata' => $this->metadata,
        ], fn ($value) => $value !== null);
    }
}
