<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Links de Pagamento.
 *
 * Links de pagamento permitem criar uma página de checkout
 * compartilhável para receber pagamentos via Pix ou boleto.
 *
 * @example
 * ```php
 * Transfeera::paymentLinks()->create([
 *     'name' => 'Produto X',
 *     'value' => 1990, // R$ 19,90 em centavos
 * ]);
 * Transfeera::paymentLinks()->get('link_abc123');
 * ```
 */
class PaymentLinkResource extends BaseResource
{
    private const string BASE_PATH = '/payment_links';

    /**
     * Cria um novo link de pagamento.
     *
     * @param  array<string, mixed>  $data  Dados do link de pagamento
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $data,
            $this->accountId,
        );
    }

    /**
     * Consulta um link de pagamento pelo ID.
     *
     * @param  string  $id  ID do link de pagamento
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id,
            accountId: $this->accountId,
        );
    }

    /**
     * Exclui um link de pagamento.
     *
     * @param  string  $id  ID do link de pagamento
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->connector->delete(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id,
            $this->accountId,
        );
    }
}
