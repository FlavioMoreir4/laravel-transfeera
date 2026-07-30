<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para Relatório de Extrato.
 *
 * @see https://docs.transfeera.dev/reference/statement_report
 */
class StatementReportResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $id  ID do relatório
     * @param  string  $status  Status: processing, completed, failed
     * @param  string|null  $url  URL para download (quando completed)
     * @param  string|null  $startDate  Data início do período
     * @param  string|null  $endDate  Data fim do período
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        string $id,
        string $status,
        public ?string $url = null,
        public ?string $startDate = null,
        public ?string $endDate = null,
        ?string $createdAt = null,
        ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter(array_merge(parent::toArray(), [
            'url' => $this->url,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            id: (string) ($data['id'] ?? ''),
            status: (string) ($data['status'] ?? ''),
            url: isset($data['url']) ? (string) $data['url'] : null,
            startDate: $data['start_date'] ?? null,
            endDate: $data['end_date'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
