<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\DTOs\Response\PaymentLinkResponseDTO;
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
 * Transfeera::paymentLinks()->list(['status' => 'active']);
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
     */
    public function create(array $data): PaymentLinkResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $data, PaymentLinkResponseDTO::class);
    }

    /**
     * Lista os links de pagamento cadastrados.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, name, paginação)
     * @return array<int, PaymentLinkResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $filters, PaymentLinkResponseDTO::class);
    }

    /**
     * Consulta um link de pagamento pelo ID.
     *
     * @param  string  $id  ID do link de pagamento
     */
    public function get(string $id): PaymentLinkResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, [], PaymentLinkResponseDTO::class);
    }

    /**
     * Exclui um link de pagamento.
     *
     * @param  string  $id  ID do link de pagamento
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->deleteRaw(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id);
    }
}
