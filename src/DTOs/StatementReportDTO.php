<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs;

/**
 * DTO para solicitação de relatório de extrato.
 *
 * @see https://docs.transfeera.dev/reference/post_statement-report.md
 */
readonly class StatementReportDTO
{
    /**
     * @param  string  $startDate  Data início (Y-m-d)
     * @param  string  $endDate    Data fim (Y-m-d)
     */
    public function __construct(
        public string $startDate,
        public string $endDate,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'data_inicio' => $this->startDate,
            'data_fim' => $this->endDate,
        ];
    }
}