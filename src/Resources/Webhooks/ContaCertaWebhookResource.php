<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Webhooks;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Webhooks de Conta Certa / Validações.
 *
 * Permite configurar URLs de callback e consultar eventos
 * relacionados a validações de conta bancária.
 *
 * @example
 * ```php
 * Transfeera::contaCertaWebhooks()->createUrl(['url' => 'https://...']);
 * Transfeera::contaCertaWebhooks()->listEvents();
 * ```
 */
class ContaCertaWebhookResource extends BaseResource
{
    private const string BASE_PATH_URLS = '/webhook';

    private const string BASE_PATH_EVENTS = '/webhook/event';

    /**
     * Cria uma nova URL de webhook para Conta Certa.
     *
     * @param  array{url: string, events?: string[]}  $data  URL e eventos opcionais
     * @return array<string, mixed>
     */
    public function createUrl(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_URLS,
            $data,
            $this->accountId,
        );
    }

    /**
     * Consulta uma URL de webhook pelo ID.
     *
     * @param  string  $id  ID da URL de webhook
     * @return array<string, mixed>
     */
    public function getUrl(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_URLS.'/'.$id,
            accountId: $this->accountId,
        );
    }

    /**
     * Lista todas as URLs de webhook cadastradas.
     *
     * @return array<string, mixed>
     */
    public function listUrls(): array
    {
        return $this->connector->get(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_URLS,
            accountId: $this->accountId,
        );
    }

    /**
     * Atualiza uma URL de webhook.
     *
     * @param  string  $id  ID da URL de webhook
     * @param  array{url?: string, events?: string[]}  $data  Dados a serem atualizados
     * @return array<string, mixed>
     */
    public function updateUrl(string $id, array $data): array
    {
        return $this->connector->put(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_URLS.'/'.$id,
            $data,
            $this->accountId,
        );
    }

    /**
     * Remove uma URL de webhook.
     *
     * @param  string  $id  ID da URL de webhook
     * @return array<string, mixed>
     */
    public function deleteUrl(string $id): array
    {
        return $this->connector->delete(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_URLS.'/'.$id,
            $this->accountId,
        );
    }

    /**
     * Lista os eventos de webhook de Conta Certa.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais
     * @return array<string, mixed>
     */
    public function listEvents(array $filters = []): array
    {
        return $this->connector->get(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_EVENTS,
            $filters,
            $this->accountId,
        );
    }

    /**
     * Reenvia um evento de webhook de Conta Certa.
     *
     * @param  string  $eventId  ID do evento
     * @return array<string, mixed>
     */
    public function resendEvent(string $eventId): array
    {
        return $this->connector->post(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH_EVENTS.'/'.$eventId.'/retry',
            accountId: $this->accountId,
        );
    }
}
