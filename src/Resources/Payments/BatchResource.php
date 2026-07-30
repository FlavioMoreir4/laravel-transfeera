<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BatchResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de lotes de pagamentos.
 *
 * @see https://docs.transfeera.dev/reference/post_batch.md
 */
class BatchResource extends BaseResource
{
    /**
     * @var string Domínio da API de pagamentos
     */
    protected const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria um novo lote de pagamentos.
     *
     * @param  BatchDTO|array<string, mixed>  $data  Dados do lote
     */
    public function create(BatchDTO|array $data): BatchResponseDTO
    {
        $payload = $data instanceof BatchDTO ? $data->toArray() : $data;

        return $this->postDTO(self::DOMAIN, '/batch', $payload, BatchResponseDTO::class);
    }

    /**
     * Retorna os detalhes de um lote pelo ID.
     *
     * @param  string  $id  Identificador do lote
     */
    public function get(string $id): BatchResponseDTO
    {
        return $this->getDTO(self::DOMAIN, "/batch/{$id}", [], BatchResponseDTO::class);
    }

    /**
     * Lista lotes com paginação e filtros.
     *
     * @param  array<string, mixed>  $params  Parâmetros de filtro (page, per_page, status, etc.)
     * @return array<int, BatchResponseDTO>
     */
    public function list(array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, '/batch', $params, BatchResponseDTO::class);
    }

    /**
     * Atualiza os dados de um lote.
     *
     * @param  string  $id  Identificador do lote
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     */
    public function update(string $id, array $data): BatchResponseDTO
    {
        return $this->putDTO(self::DOMAIN, "/batch/{$id}", $data, BatchResponseDTO::class);
    }

    /**
     * Remove um lote.
     *
     * @param  string  $id  Identificador do lote
     * @return array<string, mixed> Confirmação de exclusão
     */
    public function delete(string $id): array
    {
        return $this->deleteRaw(self::DOMAIN, "/batch/{$id}");
    }

    /**
     * Processa (fecha) um lote, enviando as transferências para execução.
     *
     * @param  string  $id  Identificador do lote
     * @return BatchResponseDTO Lote processado
     */
    public function process(string $id): BatchResponseDTO
    {
        $response = $this->connector->post(
            self::DOMAIN,
            "/batch/{$id}/close",
            [],
            $this->accountId,
        );

        return $this->toDTO(BatchResponseDTO::class, $response);
    }
}
