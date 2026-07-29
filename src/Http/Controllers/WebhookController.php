<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http\Controllers;

use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use FlavioMoreir4\Transfeera\Webhooks\SignatureValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Controller base para receber webhooks da Transfeera.
 *
 * Valida a assinatura HMAC-SHA256, extrai o domínio/tipo do evento
 * e dispara o evento Laravel {@see TransfeeraWebhookReceived}.
 *
 * As rotas podem ser publicadas pelo ServiceProvider.
 */
class WebhookController extends Controller
{
    /**
     * Recebe webhooks de pagamentos.
     */
    public function payments(Request $request): Response
    {
        return $this->handle($request, 'payments', 'X-Signature');
    }

    /**
     * Recebe webhooks de recebimentos.
     */
    public function receivables(Request $request): Response
    {
        return $this->handle($request, 'receivables', 'X-Signature');
    }

    /**
     * Recebe webhooks de Conta Certa.
     */
    public function contaCerta(Request $request): Response
    {
        return $this->handle($request, 'conta_certa', 'X-Signature');
    }

    /**
     * Processa o webhook: valida, dispara evento e responde 200.
     *
     * @param  string  $signatureHeader  Nome do header de assinatura
     */
    private function handle(Request $request, string $domain, string $signatureHeader): Response
    {
        $payload = $request->getContent();
        $signature = (string) $request->header($signatureHeader);
        $secret = config("transfeera.webhook_secrets.{$domain}", config('transfeera.webhook_secret'));

        if ($secret === null || $secret === '') {
            return new Response('Webhook secret not configured', 500);
        }

        $validator = new SignatureValidator($secret);
        $isValid = $domain === 'receivables'
            ? $validator->isValidForReceivables($payload, $signature)
            : $validator->isValid($payload, $signature);

        if (! $isValid) {
            return new Response('Invalid signature', 401);
        }

        $eventPayload = $request->json()?->all() ?? [];
        $eventType = $eventPayload['event'] ?? 'unknown';

        TransfeeraWebhookReceived::dispatch(
            $domain,
            $eventType,
            $eventPayload,
        );

        return new Response('OK', 200);
    }
}
