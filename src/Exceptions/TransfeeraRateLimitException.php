<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Exceptions;

use Throwable;

/**
 * Lançada quando o rate limit da API é excedido (HTTP 429).
 *
 * Expõe headers de rate limit para que a aplicação possa
 * implementar backoff e monitoramento.
 */
class TransfeeraRateLimitException extends TransfeeraException
{
    /**
     * @param  string  $message  Mensagem de erro
     * @param  int  $statusCode  Código HTTP
     * @param  array<string, mixed>|null  $payload  Resposta da API
     * @param  int|null  $retryAfter  Segundos até poder tentar novamente (header Retry-After)
     * @param  int|null  $limit  Limite de requisições (header X-RateLimit-Limit)
     * @param  int|null  $remaining  Requisições restantes (header X-RateLimit-Remaining)
     * @param  int|null  $reset  Timestamp de reset (header X-RateLimit-Reset)
     */
    public function __construct(
        string $message = '',
        int $statusCode = 0,
        ?array $payload = null,
        private readonly ?int $retryAfter = null,
        private readonly ?int $limit = null,
        private readonly ?int $remaining = null,
        private readonly ?int $reset = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $payload, $previous);
    }

    /**
     * Segundos recomendados para aguardar antes de tentar novamente.
     *
     * Usa o header Retry-After quando disponível; caso contrário,
     * retorna o valor derivado de X-RateLimit-Reset.
     */
    public function getRetryAfter(): ?int
    {
        if ($this->retryAfter !== null) {
            return $this->retryAfter;
        }

        if ($this->reset !== null && $this->reset > time()) {
            return $this->reset - time();
        }

        return null;
    }

    /**
     * Limite de requisições permitidas na janela atual.
     */
    public function getLimit(): ?int
    {
        return $this->limit;
    }

    /**
     * Requisições restantes na janela atual.
     */
    public function getRemaining(): ?int
    {
        return $this->remaining;
    }

    /**
     * Timestamp Unix de quando o rate limit será resetado.
     */
    public function getReset(): ?int
    {
        return $this->reset;
    }
}
