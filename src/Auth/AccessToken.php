<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Auth;

/**
 * Value object que representa um token de acesso OAuth 2.0.
 *
 * Imutável e auto-descritivo: sabe informar se está expirado.
 */
readonly class AccessToken
{
    /**
     * @param  string  $token    O token JWT de acesso
     * @param  int     $expiresAt  Timestamp Unix de quando o token expira
     */
    public function __construct(
        private string $token,
        private int $expiresAt,
    ) {}

    /**
     * Cria uma instância a partir da resposta da API.
     *
     * Aplica margem de segurança de 60 segundos antes do expiry real.
     *
     * @param  array{access_token: string, expires_in: int}  $response
     */
    public static function fromResponse(array $response): self
    {
        $expiresIn = (int) ($response['expires_in'] ?? 1800);

        return new self(
            token: $response['access_token'],
            expiresAt: time() + $expiresIn - 60,
        );
    }

    /** O token JWT de acesso. */
    public function token(): string
    {
        return $this->token;
    }

    /** Timestamp Unix de expiração (com margem de segurança). */
    public function expiresAt(): int
    {
        return $this->expiresAt;
    }

    /** O token ainda é válido (dentro do prazo com margem)? */
    public function isValid(): bool
    {
        return time() < $this->expiresAt;
    }

    /** O token já expirou? */
    public function isExpired(): bool
    {
        return ! $this->isValid();
    }
}
