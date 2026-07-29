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
 * @see https://docs.transfeera.dev/reference/billets
 */
class BilletResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria um novo boleto.
     *
     * @param  array<string, mixed>  $data  Dados do boleto (beneficiário, valor, vencimento, etc.)
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/v1/billets',
            $data,
            $this->accountId,
        );
    }

    /**
     * Atualiza os dados de um boleto.
     *
     * @param  string  $id    Identificador do boleto
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->connector->patch(
            self::DOMAIN,
            "/v1/billets/{$id}",
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
            "/v1/billets/{$id}",
            [],
            $this->accountId,
        );
    }

    /**
     * Lista boletos com filtros e paginação.
     *
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/v1/billets',
            $params,
            $this->accountId,
        );
    }

    /**
     * Remove um boleto.
     *
     * @param  string  $id  Identificador do boleto
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->connector->delete(
            self::DOMAIN,
            "/v1/billets/{$id}",
            $this->accountId,
        );
    }

    /**
     * Consulta a situação do boleto na CIP.
     *
     * @param  string  $id  Identificador do boleto
     * @return array<string, mixed>
     */
    public function consultCip(string $id): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/v1/billets/{$id}/cip",
            [],
            $this->accountId,
        );
    }
}
