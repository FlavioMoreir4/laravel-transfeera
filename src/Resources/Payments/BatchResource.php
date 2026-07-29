<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de lotes (batches) de pagamentos.
 *
 * Um lote agrupa múltiplas transferências que são processadas em conjunto.
 *
 * @see https://docs.transfeera.dev/reference/batches
 */
class BatchResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria um novo lote de pagamentos.
     *
     * @param  array<string, mixed>  $data  Dados do lote (ex.: nome, tipo)
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/batch',
            $data,
            $this->accountId,
        );
    }

    /**
     * Retorna os detalhes de um lote específico.
     *
     * @param  string  $id  Identificador único do lote
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/batch/{$id}",
            [],
            $this->accountId,
        );
    }

    /**
     * Lista todos os lotes com paginação.
     *
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/batch',
            $params,
            $this->accountId,
        );
    }

    /**
     * Atualiza os dados de um lote.
     *
     * @param  string  $id    Identificador do lote
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->connector->patch(
            self::DOMAIN,
            "/batch/{$id}",
            $data,
            $this->accountId,
        );
    }

    /**
     * Remove um lote.
     *
     * @param  string  $id  Identificador do lote
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->connector->delete(
            self::DOMAIN,
            "/batch/{$id}",
            $this->accountId,
        );
    }

    /**
     * Processa (fecha) um lote, enviando as transferências para execução.
     *
     * @param  string  $id  Identificador do lote
     * @return array<string, mixed>
     */
    public function process(string $id): array
    {
        return $this->connector->post(
            self::DOMAIN,
            "/batch/{$id}/close",
            [],
            $this->accountId,
        );
    }
}
