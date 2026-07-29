<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

/**
 * Lançada quando a API retorna erros de validação (HTTP 422).
 *
 * Carrega os erros de campo retornados pela API para facilitar
 * a depuração e exibição ao usuário.
 */
class TransfeeraValidationException extends TransfeeraException
{
    /**
     * @param  array<string, mixed>  $errors  Mapa de erros por campo
     */
    public function __construct(
        string $message = 'Erro de validação',
        int $statusCode = 422,
        private readonly array $errors = [],
        ?array $payload = null,
    ) {
        parent::__construct($message, $statusCode, $payload);
    }

    /**
     * Retorna os erros de validação agrupados por campo.
     *
     * @return array<string, mixed>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
