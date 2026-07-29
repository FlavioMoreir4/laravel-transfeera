<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;

test('recebe webhook de pagamentos com assinatura valida', function () {
    Event::fake([TransfeeraWebhookReceived::class]);

    config()->set('transfeera.webhook_secret', 'secret-payments');

    $payload = '{"event":"batch.processed","data":{"id":"batch_1"}}';
    $signature = hash_hmac('sha256', $payload, 'secret-payments');

    $response = $this->postJson('/webhooks/transfeera/payments', json_decode($payload, true), [
        'X-Signature' => $signature,
    ]);

    $response->assertOk();
    Event::assertDispatched(TransfeeraWebhookReceived::class, function ($event) {
        return $event->domain === 'payments' && $event->type === 'batch.processed';
    });
});

test('rejeita webhook com assinatura invalida', function () {
    config()->set('transfeera.webhook_secret', 'secret-payments');

    $response = $this->postJson('/webhooks/transfeera/payments', [
        'event' => 'batch.processed',
        'data' => ['id' => 'batch_1'],
    ], [
        'X-Signature' => 'invalid',
    ]);

    $response->assertUnauthorized();
});

test('recebe webhook de recebimentos com assinatura valida', function () {
    Event::fake([TransfeeraWebhookReceived::class]);

    config()->set('transfeera.webhook_secret', 'secret-receivables');

    $payload = '{"event":"pix.received","data":{"end2end":"E2E123"}}';
    $signature = hash_hmac('sha256', $payload, 'secret-receivables');

    $response = $this->postJson('/webhooks/transfeera/receivables', json_decode($payload, true), [
        'X-Signature' => $signature,
    ]);

    $response->assertOk();
    Event::assertDispatched(TransfeeraWebhookReceived::class, function ($event) {
        return $event->domain === 'receivables' && $event->type === 'pix.received';
    });
});

test('retorna 500 quando webhook secret nao esta configurado', function () {
    config()->set('transfeera.webhook_secret', null);

    $response = $this->postJson('/webhooks/transfeera/payments', [
        'event' => 'batch.processed',
    ]);

    $response->assertStatus(500);
});
