<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Webhooks;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\WebhookEventResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\WebhookResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Webhooks de Recebimentos.
 *
 * Permite configurar URLs de callback e consultar/reenviar eventos
 * relacionados a recebimentos (chaves Pix, QR codes, cobranças, etc.).
 * Recebimentos possuem regras próprias de verificação de assinatura.
 *
 * @example
 * ```php
 * Transfeera::receivablesWebhooks()->createUrl(['url' => 'https://...']);
 * Transfeera::receivablesWebhooks()->listEvents();
 * ```
 */
class ReceivablesWebhookResource extends BaseResource
{
    private const string BASE_PATH_URLS = '/webhook';

    private const string BASE_PATH_EVENTS = '/webhook/event';

    /**
     * Cria uma nova URL de webhook para recebimentos.
     *
     * @param  array{url: string, events?: string[]}  $data  URL e eventos opcionais
     */
    public function createUrl(array $data): WebhookResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_URLS, $data, WebhookResponseDTO::class);
    }

    /**
     * Consulta uma URL de webhook pelo ID.
     *
     * @param  string  $id  ID da URL de webhook
     */
    public function getUrl(string $id): WebhookResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_URLS.'/'.$id, [], WebhookResponseDTO::class);
    }

    /**
     * Lista todas as URLs de webhook cadastradas.
     *
     * @return array<int, WebhookResponseDTO>
     */
    public function listUrls(): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_URLS, [], WebhookResponseDTO::class);
    }

    /**
     * Atualiza uma URL de webhook.
     *
     * @param  string  $id  ID da URL de webhook
     * @param  array{url?: string, events?: string[]}  $data  Dados a serem atualizados
     */
    public function updateUrl(string $id, array $data): WebhookResponseDTO
    {
        return $this->putDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_URLS.'/'.$id, $data, WebhookResponseDTO::class);
    }

    /**
     * Remove uma URL de webhook.
     *
     * @param  string  $id  ID da URL de webhook
     */
    public function deleteUrl(string $id): OperationResponseDTO
    {
        return $this->deleteDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_URLS.'/'.$id, OperationResponseDTO::class);
    }

    /**
     * Lista os eventos de webhook de recebimentos.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais
     * @return array<int, WebhookEventResponseDTO>
     */
    public function listEvents(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_EVENTS, $filters, WebhookEventResponseDTO::class);
    }

    /**
     * Reenvia um evento de webhook de recebimentos.
     *
     * @param  string  $eventId  ID do evento
     */
    public function resendEvent(string $eventId): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH_EVENTS.'/'.$eventId.'/retry', [], OperationResponseDTO::class);
    }
}
