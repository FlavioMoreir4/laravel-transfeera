<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\BilletCipResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BilletResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
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
    private const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria um novo boleto dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $data  Dados do boleto (beneficiário, valor, vencimento, etc.)
     */
    public function create(string $batchId, array $data): BilletResponseDTO
    {
        return $this->postDTO(self::DOMAIN, "/batch/{$batchId}/billet", $data, BilletResponseDTO::class);
    }

    /**
     * Cria um novo boleto avulso (fora de lote).
     *
     * @param  array<string, mixed>  $data  Dados do boleto
     */
    public function createStandalone(array $data): BilletResponseDTO
    {
        return $this->postDTO(self::DOMAIN, '/billet', $data, BilletResponseDTO::class);
    }

    /**
     * Atualiza os dados de um boleto dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $id  Identificador do boleto
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     */
    public function update(string $batchId, string $id, array $data): BilletResponseDTO
    {
        return $this->putDTO(self::DOMAIN, "/batch/{$batchId}/billet/{$id}", $data, BilletResponseDTO::class);
    }

    /**
     * Atualiza os dados de um boleto avulso.
     *
     * @param  string  $id  Identificador do boleto
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     */
    public function updateStandalone(string $id, array $data): BilletResponseDTO
    {
        return $this->putDTO(self::DOMAIN, "/billet/{$id}", $data, BilletResponseDTO::class);
    }

    /**
     * Retorna os detalhes de um boleto.
     *
     * @param  string  $id  Identificador do boleto
     */
    public function get(string $id): BilletResponseDTO
    {
        return $this->getDTO(self::DOMAIN, "/billet/{$id}", [], BilletResponseDTO::class);
    }

    /**
     * Lista boletos dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<int, BilletResponseDTO>
     */
    public function list(string $batchId, array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, "/batch/{$batchId}/billet", $params, BilletResponseDTO::class);
    }

    /**
     * Lista boletos avulsos com filtros e paginação.
     *
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status, etc.)
     * @return array<int, BilletResponseDTO>
     */
    public function listStandalone(array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, '/billet', $params, BilletResponseDTO::class);
    }

    /**
     * Remove um boleto de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $id  Identificador do boleto
     */
    public function delete(string $batchId, string $id): OperationResponseDTO
    {
        return $this->deleteDTO(self::DOMAIN, "/batch/{$batchId}/billet/{$id}", OperationResponseDTO::class);
    }

    /**
     * Remove um boleto avulso.
     *
     * @param  string  $id  Identificador do boleto
     */
    public function deleteStandalone(string $id): OperationResponseDTO
    {
        return $this->deleteDTO(self::DOMAIN, "/billet/{$id}", OperationResponseDTO::class);
    }

    /**
     * Consulta a situação do boleto na CIP.
     *
     * @param  string  $id  Identificador do boleto (passado como query param)
     */
    public function consultCip(string $id): BilletCipResponseDTO
    {
        return $this->getDTO(self::DOMAIN, '/billet/consult', ['id' => $id], BilletCipResponseDTO::class);
    }
}
