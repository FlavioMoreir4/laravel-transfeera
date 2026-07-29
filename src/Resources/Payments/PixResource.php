<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para operações Pix no módulo de pagamentos.
 *
 * Inclui consulta de chave Pix (DICT) e parser de Pix copia-e-cola (EMV).
 *
 * @see https://docs.transfeera.dev/reference/pix
 */
class PixResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Consulta uma chave Pix no DICT para obter os dados do recebedor.
     *
     * @param  string  $pixKey  Chave Pix (CPF, CNPJ, e-mail, telefone ou aleatória)
     * @return array<string, mixed>
     */
    public function lookupKey(string $pixKey): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/pix/dict_key/' . $pixKey,
            [],
            $this->accountId,
        );
    }

    /**
     * Interpreta (parse) um código Pix copia-e-cola (EMV) para extrair
     * os dados estruturados do pagamento.
     *
     * @param  string  $emv  Código Pix copia-e-cola (EMV QR Code)
     * @return array<string, mixed>
     */
    public function parseEmv(string $emv): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/pix/qrcode/parse',
            ['emv' => $emv],
            $this->accountId,
        );
    }
}
