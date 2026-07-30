<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta para análise de infração MED.
 *
 * @see https://docs.transfeera.dev/reference/post_infractions-id-analyse.md
 */
class InfractionAnalysisResponseDTO extends BaseResponseDTO
{
    /**
     * @param  string  $analysisId  ID da análise
     * @param  string  $result  Resultado: approved, rejected, pending_reply
     * @param  string|null  $infractionId  ID da infração analisada
     * @param  string|null  $replyDeadline  Prazo para réplica
     * @param  string|null  $comment  Comentário da análise
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public string $analysisId,
        public string $result,
        public ?string $infractionId = null,
        public ?string $replyDeadline = null,
        public ?string $comment = null,
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
            'analysis_id' => $this->analysisId,
            'result' => $this->result,
            'infraction_id' => $this->infractionId,
            'reply_deadline' => $this->replyDeadline,
            'comment' => $this->comment,
        ]), fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            analysisId: $data['analysis_id'] ?? $data['id_analise'] ?? '',
            result: $data['result'] ?? $data['resultado'] ?? '',
            infractionId: $data['infraction_id'] ?? $data['id_infracao'] ?? null,
            replyDeadline: $data['reply_deadline'] ?? $data['prazo_replica'] ?? null,
            comment: $data['comment'] ?? $data['comentario'] ?? null,
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
        );
    }
}
