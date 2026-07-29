<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de boletos.
 *
 * Permite criar, editar, consultar e remover boletos,
 * além de consultar a situação na CIP.
 *
 * Operações de create/update/delete em lote requerem o batchId.
 * Operações standalone (get, list, consultCip) não precisam.
 *
 * @see https://docs.transfeera.dev/reference/billets
 */
class BilletResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria um novo boleto dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $data  Dados do boleto (beneficiário, valor, vencimento, etc.)
     * @return array<string, mixed>
     */
    public function create(string $batchId, array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            "/batch/{$batchId}/billet",
            $data,
            $this->accountId,
        );
    }

    /**
     * Cria um novo boleto avulso (fora de lote).
     *
     * @param  array<string, mixed>  $data  Dados do boleto
     * @return array<string, mixed>
     */
    public function createStandalone(array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/billet',
            $data,
            $this->accountId,
        );
    }

    /**
     * Atualiza os dados de um boleto dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $id       Identificador do boleto
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function update(string $batchId, string $id, array $data): array
    {
        return $this->connector->put(
            self::DOMAIN,
            "/batch/{$batchId}/billet/{$id}",
            $data,
            $this->accountId,
        );
    }

    /**
     * Atualiza os dados de um boleto avulso.
     *
     * @param  string  $id    Identificador do boleto
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function updateStandalone(string $id, array $data): array
    {
        return $this->connector->put(
            self::DOMAIN,
            "/billet/{$id}",
            $data,
            $this->accountId,
        );
    }

    /**
     * Retorna os detalhes de um boleto.
     *
     * @param  string  $id  Identificador do boleto
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/billet/{$id}",
            [],
            $this->accountId,
        );
    }

    /**
     * Lista boletos dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<string, mixed>
     */
    public function list(string $batchId, array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/batch/{$batchId}/billet",
            $params,
            $this->accountId,
        );
    }

    /**
     * Lista boletos avulsos com filtros e paginação.
     *
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<string, mixed>
     */
    public function listStandalone(array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/billet',
            $params,
            $this->accountId,
        );
    }

    /**
     * Remove um boleto de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $id       Identificador do boleto
     * @return array<string, mixed>
     */
    public function delete(string $batchId, string $id): array
    {
        return $this->connector->delete(
            self::DOMAIN,
            "/batch/{$batchId}/billet/{$id}",
            $this->accountId,
        );
    }

    /**
     * Remove um boleto avulso.
     *
     * @param  string  $id  Identificador do boleto
     * @return array<string, mixed>
     */
    public function deleteStandalone(string $id): array
    {
        return $this->connector->delete(
            self::DOMAIN,
            "/billet/{$id}",
            $this->accountId,
        );
    }

    /**
     * Consulta a situação do boleto na CIP.
     *
     * @param  string  $id  Identificador do boleto (passado como query param)
     * @return array<string, mixed>
     */
    public function consultCip(string $id): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/billet/consult',
            ['id' => $id],
            $this->accountId,
        );
    }
}
