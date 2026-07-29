<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Listeners;

use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use Illuminate\Support\Facades\Log;

/**
 * Listener de exemplo que loga todo webhook recebido.
 *
 * Descomente no EventServiceProvider do usuário ou deixe registrado
 * automaticamente pelo package provider.
 */
class LogTransfeeraWebhook
{
    public function handle(TransfeeraWebhookReceived $event): void
    {
        Log::info('[Transfeera Webhook]', [
            'domain' => $event->domain,
            'type' => $event->type,
            'payload' => $event->payload,
        ]);
    }
}
