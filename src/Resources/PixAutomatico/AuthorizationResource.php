<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\PixAutomatico;

use FlavioMoreir4\Transfeera\DTOs\Response\AuthorizationResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Autorizações Pix Automático.
 *
 * O Pix Automático permite criar autorizações de cobrança recorrente,
 * gerenciar o split de pagamento e cancelar autorizações ativas.
 *
 * @example
 * ```php
 * Transfeera::pixAutomaticoAuthorizations()->create([...]);
 * Transfeera::pixAutomaticoAuthorizations()->list();
 * Transfeera::pixAutomaticoAuthorizations()->cancel('auth_123');
 * ```
 */
class AuthorizationResource extends BaseResource
{
    private const string BASE_PATH = '/pix/automatic/authorizations';

    /**
     * Cria uma nova autorização Pix Automático.
     *
     * @param  array<string, mixed>  $data  Dados da autorização
     */
    public function create(array $data): AuthorizationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH, $data, AuthorizationResponseDTO::class);
    }

    /**
     * Lista as autorizações cadastradas.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<int, AuthorizationResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH, $filters, AuthorizationResponseDTO::class);
    }

    /**
     * Consulta uma autorização pelo ID.
     *
     * @param  string  $id  ID da autorização
     */
    public function get(string $id): AuthorizationResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id, [], AuthorizationResponseDTO::class);
    }

    /**
     * Cancela uma autorização ativa.
     *
     * @param  string  $id  ID da autorização
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PIX_AUTOMATICO,
            self::BASE_PATH.'/'.$id.'/cancellations',
            accountId: $this->accountId,
        );
    }

    /**
     * Consulta o cancelamento de uma autorização.
     *
     * @param  string  $id  ID da autorização
     * @param  string  $cancellationId  ID do cancelamento
     * @return array<string, mixed>
     */
    public function getCancellation(string $id, string $cancellationId): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PIX_AUTOMATICO,
            self::BASE_PATH.'/'.$id.'/cancellations/'.$cancellationId,
            accountId: $this->accountId,
        );
    }

    /**
     * Atualiza uma autorização (ex.: alterar split_payment).
     *
     * @param  string  $id  ID da autorização
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     */
    public function update(string $id, array $data): AuthorizationResponseDTO
    {
        return $this->patchDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id, $data, AuthorizationResponseDTO::class);
    }
}
