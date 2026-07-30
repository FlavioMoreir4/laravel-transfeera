<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Webhooks;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento genérico de webhook disparado pelo SDK.
 *
 * O consumidor pode escutar eventos por tipo:
 *
 * ```php
 * // No EventServiceProvider do app consumidor
 * Event::listen(WebhookEvent::class, function (WebhookEvent $event) {
 *     Log::info('Webhook recebido', [
 *         'type' => $event->type,
 *         'domain' => $event->domain,
 *         'payload' => $event->payload,
 *     ]);
 * });
 * ```
 */
class WebhookEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param  string  $domain  Domínio de origem (payments|receivables|conta_certa)
     * @param  string  $type  Tipo do evento (ex.: batch.processed, pix.received)
     * @param  array<string, mixed>  $payload  Dados do evento
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $type,
        public readonly array $payload,
    ) {}

    /**
     * Cria uma instância a partir de um payload bruto de webhook.
     *
     * @param  string  $domain  Domínio de origem
     * @param  array<string, mixed>  $data  Dados do webhook recebido
     */
    public static function fromPayload(string $domain, array $data): self
    {
        return new self(
            domain: $domain,
            type: $data['event'] ?? $data['type'] ?? 'unknown',
            payload: $data,
        );
    }
}
