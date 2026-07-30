<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\TransferResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;
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
    protected const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Cria uma nova transferência dentro de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  TransferDTO|array<string, mixed>  $data  Dados da transferência
     */
    public function create(string $batchId, TransferDTO|array $data): TransferResponseDTO
    {
        $payload = $data instanceof TransferDTO ? $data->toArray() : $data;

        return $this->postDTO(self::DOMAIN, "/batch/{$batchId}/transfer", $payload, TransferResponseDTO::class);
    }

    /**
     * Retorna os detalhes de uma transferência pelo ID.
     *
     * Suporta tanto o path standalone `/transfer/{id}` quanto
     * o path contextual `/batch/{batchId}/transfer/{id}`.
     *
     * @param  string  $transferId  Identificador da transferência
     * @param  string|null  $batchId  Identificador do lote (opcional)
     */
    public function get(string $transferId, ?string $batchId = null): TransferResponseDTO
    {
        $path = $batchId !== null
            ? "/batch/{$batchId}/transfer/{$transferId}"
            : "/transfer/{$transferId}";

        return $this->getDTO(self::DOMAIN, $path, [], TransferResponseDTO::class);
    }

    /**
     * Lista as transferências de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status)
     * @return array<int, TransferResponseDTO>
     */
    public function list(string $batchId, array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, "/batch/{$batchId}/transfer", $params, TransferResponseDTO::class);
    }

    /**
     * Atualiza os dados de uma transferência.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $transferId  Identificador da transferência
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     */
    public function update(string $batchId, string $transferId, array $data): TransferResponseDTO
    {
        return $this->putDTO(self::DOMAIN, "/batch/{$batchId}/transfer/{$transferId}", $data, TransferResponseDTO::class);
    }

    /**
     * Remove uma transferência de um lote.
     *
     * @param  string  $batchId  Identificador do lote
     * @param  string  $transferId  Identificador da transferência
     * @return array<string, mixed> Confirmação de exclusão
     */
    public function delete(string $batchId, string $transferId): array
    {
        return $this->deleteRaw(self::DOMAIN, "/batch/{$batchId}/transfer/{$transferId}");
    }
}
