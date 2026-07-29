<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para envio de análise de infração individual (MED).
 *
 * @see https://docs.transfeera.dev/reference/post-med-infractions-id-analysis.md
 */
readonly class InfractionAnalysisDTO
{
    /**
     * @param  string  $type             Tipo: 'refund', 'contest'
     * @param  int|null $refundAmount    Valor da devolução em centavos (para type=refund)
     * @param  string|null $description  Descrição da análise
     */
    public function __construct(
        public string $type,
        public ?int $refundAmount = null,
        public ?string $description = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'refund_amount' => $this->refundAmount,
            'description' => $this->description,
        ], fn ($value) => $value !== null);
    }
}