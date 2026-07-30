<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

use Throwable;

/**
 * Exceção para erros na API de Pix Automático.
 *
 * Cobre: autorizações, instruções de pagamento (payment intents).
 *
 * @see https://docs.transfeera.dev/reference/tag/Pix-Autom%C3%A1tico
 */
class PixAutomaticoException extends TransfeeraException
{
    /**
     * @param  array<string, mixed>|null  $payload
     */
    public function __construct(
        string $message = 'Erro na API de Pix Automático',
        int $statusCode = 0,
        ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $payload, $previous);
    }

    public static function fromResponse(array $payload, int $statusCode): self
    {
        $message = $payload['message'] ?? $payload['error'] ?? "Erro na API de Pix Automático (HTTP {$statusCode})";
        return new self($message, $statusCode, $payload);
    }
}