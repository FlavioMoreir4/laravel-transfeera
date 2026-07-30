<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

use Override;

/**
 * DTO de resposta genérico para operações simples.
 *
 * Útil para endpoints de ação (cancelar, remover, verificar, etc.)
 * que retornam apenas status da operação e mensagem.
 *
 * @see https://docs.transfeera.dev/reference/endpoints
 */
class OperationResponseDTO extends BaseResponseDTO
{
    /**
     * @param  bool  $success  Se a operação foi bem-sucedida
     * @param  string  $message  Mensagem descritiva da operação
     * @param  string  $id  ID do recurso afetado
     * @param  string  $status  Status do recurso após a operação
     * @param  string|null  $createdAt  Data criação
     * @param  string|null  $updatedAt  Data atualização
     */
    public function __construct(
        public bool $success = false,
        public string $message = '',
        public string $id = '',
        public string $status = '',
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {
        parent::__construct($id, $status, $createdAt, $updatedAt);
    }

    #[Override]
    public function toArray(): array
    {
        return array_filter([
            'success' => $this->success,
            'message' => $this->message,
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static
    {
        return new self(
            success: $data['success'] ?? false,
            message: $data['message'] ?? '',
            id: $data['id'] ?? '',
            status: $data['status'] ?? '',
            createdAt: $data['created_at'] ?? null,
            updatedAt: $data['updated_at'] ?? null,
        );
    }
}
