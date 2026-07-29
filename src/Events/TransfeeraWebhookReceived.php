<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Evento disparado quando um webhook da Transfeera é recebido e validado.
 *
 * Ouvintes podem usar $event->domain, $event->type e $event->payload
 * para reagir a eventos específicos.
 */
class TransfeeraWebhookReceived
{
    use Dispatchable;
    use SerializesModels;

    /**
     * @param  string  $domain   Domínio do webhook ('payments', 'receivables', 'conta_certa')
     * @param  string  $type     Tipo do evento (ex.: 'batch.processed')
     * @param  array<string, mixed>  $payload  Payload completo do webhook
     */
    public function __construct(
        public readonly string $domain,
        public readonly string $type,
        public readonly array $payload,
    ) {}
}
