<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\PixAutomatico;

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
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $data,
            $this->accountId,
        );
    }

    /**
     * Lista as autorizações cadastradas.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $filters,
            $this->accountId,
        );
    }

    /**
     * Consulta uma autorização pelo ID.
     *
     * @param  string  $id  ID da autorização
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id,
            accountId: $this->accountId,
        );
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
            Connector::DOMAIN_PAYMENTS,
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
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/cancellations/'.$cancellationId,
            accountId: $this->accountId,
        );
    }

    /**
     * Atualiza uma autorização (ex.: alterar split_payment).
     *
     * @param  string  $id  ID da autorização
     * @param  array<string, mixed>  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function update(string $id, array $data): array
    {
        return $this->connector->patch(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id,
            $data,
            $this->accountId,
        );
    }
}
