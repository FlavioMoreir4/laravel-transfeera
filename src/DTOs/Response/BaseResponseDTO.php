<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

/**
 * DTO base para respostas da API Transfeera.
 *
 * Contém campos comuns: id, status, created_at, updated_at.
 */
abstract class BaseResponseDTO
{
    /**
     * @param  string  $id  Identificador único
     * @param  string  $status  Status do recurso
     * @param  string|null  $createdAt  Data de criação
     * @param  string|null  $updatedAt  Data de atualização
     */
    public function __construct(
        public string $id,
        public string $status,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
    ) {}

    /**
     * Converte para array filtrando valores nulos.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'id' => $this->id,
            'status' => $this->status,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ], fn ($value) => $value !== null);
    }

    /**
     * Cria instância a partir da resposta da API.
     *
     * @param  array<string, mixed>  $data
     */
    abstract public static function fromResponse(array $data): static;
}
