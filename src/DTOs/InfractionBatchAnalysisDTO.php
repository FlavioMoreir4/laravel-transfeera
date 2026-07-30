<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para envio de análise de infrações em lote (MED).
 *
 * @see https://docs.transfeera.dev/reference/post-med-infractions-analysis.md
 */
readonly class InfractionBatchAnalysisDTO
{
    /**
     * @param  array<InfractionAnalysisDTO>  $analyses  Lista de análises
     */
    public function __construct(
        public array $analyses,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'analyses' => array_map(fn ($dto) => $dto->toArray(), $this->analyses),
        ];
    }
}
