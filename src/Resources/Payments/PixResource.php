<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\PixEmvResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixResponseDTO;
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
    private const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Consulta uma chave Pix no DICT para obter os dados do recebedor.
     *
     * @param  string  $pixKey  Chave Pix (CPF, CNPJ, e-mail, telefone ou aleatória)
     */
    public function lookupKey(string $pixKey): PixResponseDTO
    {
        return $this->getDTO(self::DOMAIN, '/pix/dict_key/'.$pixKey, [], PixResponseDTO::class);
    }

    /**
     * Interpreta (parse) um código Pix copia-e-cola (EMV) para extrair
     * os dados estruturados do pagamento.
     *
     * @param  string  $emv  Código Pix copia-e-cola (EMV QR Code)
     */
    public function parseEmv(string $emv): PixEmvResponseDTO
    {
        return $this->postDTO(self::DOMAIN, '/pix/qrcode/parse', ['emv' => $emv], PixEmvResponseDTO::class);
    }
}
