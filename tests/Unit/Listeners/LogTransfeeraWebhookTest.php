<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit\Listeners;

use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use FlavioMoreir4\Transfeera\Listeners\LogTransfeeraWebhook;
use Illuminate\Support\Facades\Log;

test('loga webhook recebido', function () {
    Log::spy();

    $event = new TransfeeraWebhookReceived(
        domain: 'payments',
        type: 'batch.processed',
        payload: ['id' => 'batch_1'],
    );

    (new LogTransfeeraWebhook)->handle($event);

    Log::shouldHaveReceived('info')
        ->with('[Transfeera Webhook]', \Mockery::on(fn ($context) => $context['domain'] === 'payments'))
        ->once();
});
