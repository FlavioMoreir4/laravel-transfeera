<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\DTOs\Response\PixCashInResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para consulta e gestão de Pix recebidos (Cash-in).
 *
 * Permite consultar transações Pix recebidas por período,
 * solicitar devoluções e acompanhar o status das devoluções.
 *
 * @example
 * ```php
 * Transfeera::pixCashIn()->list(['start_date' => '2025-01-01']);
 * Transfeera::pixCashIn()->requestRefund('E2E123...', ['amount' => 100]);
 * ```
 */
class PixCashInResource extends BaseResource
{
    private const string BASE_PATH = '/pix/cashin';

    /**
     * Consulta Pix recebidos por período.
     *
     * @param  array{start_date?: string, end_date?: string, page?: int, per_page?: int}  $filters
     * @return array<int, PixCashInResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_PAYMENTS, self::BASE_PATH, $filters, PixCashInResponseDTO::class);
    }

    /**
     * Consulta um Pix recebido pelo end2endId (identificador único da transação no Pix).
     *
     * @param  string  $end2EndId  End-to-end ID da transação Pix
     */
    public function getByEnd2EndId(string $end2EndId): PixCashInResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_PAYMENTS, self::BASE_PATH.'/'.$end2EndId, [], PixCashInResponseDTO::class);
    }

    /**
     * Solicita devolução de um Pix recebido.
     *
     * O valor deve ser informado em **centavos** (inteiro).
     *
     * @param  string  $end2EndId  End-to-end ID da transação Pix
     * @param  array{amount: int, description?: string}  $data  Dados da devolução
     * @return array<string, mixed>
     */
    public function requestRefund(string $end2EndId, array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$end2EndId.'/refund',
            $data,
            $this->accountId,
        );
    }

    /**
     * Consulta as devoluções de um Pix recebido.
     *
     * @param  string  $end2EndId  End-to-end ID da transação Pix
     * @return array<string, mixed>
     */
    public function getRefunds(string $end2EndId): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$end2EndId.'/refund',
            accountId: $this->accountId,
        );
    }
}
