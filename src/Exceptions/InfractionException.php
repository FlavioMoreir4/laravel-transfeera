<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

use Throwable;

/**
 * Exceção para erros na API de Infrações (MED - Mecanismo Especial de Devolução).
 *
 * Cobre: listar infrações, consultar, enviar análise individual/lote.
 *
 * @see https://docs.transfeera.dev/reference/tag/MED/Infrações
 */
class InfractionException extends TransfeeraException
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        string $message = 'Erro na API de Infrações (MED)',
        int $statusCode = 0,
        ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $payload, $previous);
    }

    public static function fromResponse(array $payload, int $statusCode): self
    {
        $message = $payload['message'] ?? $payload['error'] ?? "Erro na API de Infrações (HTTP {$statusCode})";
        return new self($message, $statusCode, $payload);
    }
}