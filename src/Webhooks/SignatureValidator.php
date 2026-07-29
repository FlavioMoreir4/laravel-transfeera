<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Webhooks;

/**
 * Validador de assinatura de webhooks da Transfeera.
 *
 * A Transfeera utiliza assinatura HMAC-SHA256 nos payloads de webhook.
 * As regras de cálculo podem variar entre pagamentos e recebimentos.
 *
 * Uso típico em um controller de webhook:
 *
 * ```php
 * $validator = new SignatureValidator($secret);
 *
 * if (! $validator->isValid($request->getContent(), $request->header('X-Signature'))) {
 *     abort(401, 'Invalid signature');
 * }
 * ```
 */
class SignatureValidator
{
    /**
     * @param  string  $secret       Chave secreta do webhook
     * @param  bool    $isReceivables  Se true, usa regra de cálculo de recebimentos
     */
    public function __construct(
        private readonly string $secret,
        private readonly bool $isReceivables = false,
    ) {}

    /**
     * Valida a assinatura do payload do webhook.
     *
     * @param  string  $payload       Corpo bruto da requisição (JSON)
     * @param  string  $signature     Assinatura enviada no header X-Signature
     */
    public function isValid(string $payload, string $signature): bool
    {
        $expected = $this->calculate($payload);

        return hash_equals($expected, $signature);
    }

    /**
     * Calcula a assinatura esperada para o payload.
     *
     * Pagamentos e recebimentos podem usar algoritmos diferentes.
     * O padrão é HMAC-SHA256 com o secret como chave.
     */
    public function calculate(string $payload): string
    {
        if ($this->isReceivables) {
            // Recebimentos: HMAC-SHA256 do payload bruto
            return hash_hmac('sha256', $payload, $this->secret);
        }

        // Pagamentos e Conta Certa: HMAC-SHA256 do payload bruto
        return hash_hmac('sha256', $payload, $this->secret);
    }
}
