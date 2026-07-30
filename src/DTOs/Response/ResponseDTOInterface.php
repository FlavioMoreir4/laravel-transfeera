<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\DTOs\Response;

/**
 * Interface comum para todos os DTOs de resposta da API Transfeera.
 */
interface ResponseDTOInterface
{
    /**
     * Converte o DTO para array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;

    /**
     * Cria instância a partir da resposta da API.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromResponse(array $data): static;
}
