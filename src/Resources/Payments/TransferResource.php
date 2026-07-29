<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de transferências dentro de um lote.
 *
 * Cada transferência representa um pagamento individual (Pix, TED, etc.)
 * agrupado em um lote.
 *
 * @see https://docs.transfeera.dev/reference/transfers
 */
class TransferResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria uma nova transferência dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $data    Dados da transferência (valor, chave Pix, etc.)
     * @return array<string, mixed>
     */
    public function create(string $batchId, array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            "/v1/batches/{$batchId}/transfers",
            $data,
            $this->accountId,
        );
    }

    /**
     * Retorna os detalhes de uma transferência dentro de um lote.
     *
     * @param  string  $batchId     Identificador do lote
     * @param  string  $transferId  Identificador da transferência
     * @return array<string, mixed>
     */
    public function get(string $batchId, string $transferId): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/v1/batches/{$batchId}/transfers/{$transferId}",
            [],
            $this->accountId,
        );
    }

    /**
     * Lista as transferências de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $params   Filtros (page, per_page, status)
     * @return array<string, mixed>
     */
    public function list(string $batchId, array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/v1/batches/{$batchId}/transfers",
            $params,
            $this->accountId,
        );
    }

    /**
     * Atualiza os dados de uma transferência.
     *
     * @param  string  $batchId     Identificador do lote
     * @param  string  $transferId  Identificador da transferência
     * @param  array<string, mixed>  $data        Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function update(string $batchId, string $transferId, array $data): array
    {
        return $this->connector->patch(
            self::DOMAIN,
            "/v1/batches/{$batchId}/transfers/{$transferId}",
            $data,
            $this->accountId,
        );
    }

    /**
     * Remove uma transferência de um lote.
     *
     * @param  string  $batchId     Identificador do lote
     * @param  string  $transferId  Identificador da transferência
     * @return array<string, mixed>
     */
    public function delete(string $batchId, string $transferId): array
    {
        return $this->connector->delete(
            self::DOMAIN,
            "/v1/batches/{$batchId}/transfers/{$transferId}",
            $this->accountId,
        );
    }
}
