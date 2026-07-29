<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

use Exception;
use Throwable;

/**
 * Exceção base para todos os erros do SDK Transfeera.
 *
 * Carrega o payload bruto retornado pela API para inspeção.
 */
class TransfeeraException extends Exception
{
    /**
     * @param  array<string, mixed>|null  $payload  Resposta completa da API (se disponível)
     * @param  int  $statusCode  Código HTTP retornado
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        private readonly ?array $payload = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Retorna o payload bruto da resposta (se houver).
     *
     * @return array<string, mixed>|null
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }
}
