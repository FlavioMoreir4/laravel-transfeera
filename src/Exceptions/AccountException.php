<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

use Throwable;

/**
 * Exceção para erros na API de Hub de Contas.
 *
 * Cobre: contas digitais (criar, listar, consultar, encerrar).
 *
 * @see https://docs.transfeera.dev/reference/tag/Hub-de-Contas
 */
class AccountException extends TransfeeraException
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        string $message = 'Erro na API de Hub de Contas',
        int $statusCode = 0,
        ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $payload, $previous);
    }

    public static function fromResponse(array $payload, int $statusCode): self
    {
        $message = $payload['message'] ?? $payload['error'] ?? "Erro na API de Hub de Contas (HTTP {$statusCode})";
        return new self($message, $statusCode, $payload);
    }
}