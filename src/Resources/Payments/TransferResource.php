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
            "/batch/{$batchId}/transfer",
            $data,
            $this->accountId,
        );
    }

    /**
     * Retorna os detalhes de uma transferência pelo ID.
     *
     * Suporta tanto o path standalone `/transfer/{id}` quanto
     * o path contextual `/batch/{batchId}/transfer/{id}`.
     *
     * @param  string  $transferId  Identificador da transferência
     * @param  string|null  $batchId  Identificador do lote (opcional)
     * @return array<string, mixed>
     */
    public function get(string $transferId, ?string $batchId = null): array
    {
        $path = $batchId !== null
            ? "/batch/{$batchId}/transfer/{$transferId}"
            : "/transfer/{$transferId}";

        return $this->connector->get(
            self::DOMAIN,
            $path,
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
            "/batch/{$batchId}/transfer",
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
        return $this->connector->put(
            self::DOMAIN,
            "/batch/{$batchId}/transfer/{$transferId}",
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
            "/batch/{$batchId}/transfer/{$transferId}",
            $this->accountId,
        );
    }
}
