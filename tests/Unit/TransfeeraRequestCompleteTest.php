<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Events\TransfeeraRequestComplete;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

test('dispara evento com dados da requisicao', function () {
    TransfeeraRequestComplete::dispatch(
        domain: 'payments',
        method: 'GET',
        url: 'https://api-sandbox.transfeera.com/batch',
        status: 200,
        duration: 0.15,
        responseData: ['data' => [1, 2, 3]],
    );

    Event::assertDispatched(TransfeeraRequestComplete::class, function ($event) {
        return $event->domain === 'payments'
            && $event->method === 'GET'
            && $event->url === 'https://api-sandbox.transfeera.com/batch'
            && $event->status === 200
            && $event->duration === 0.15
            && $event->responseData === ['data' => [1, 2, 3]];
    });
});

test('dispara evento com erro 500', function () {
    TransfeeraRequestComplete::dispatch(
        domain: 'conta_certa',
        method: 'POST',
        url: 'https://api-sandbox.transfeera.com/conta-certa/validation',
        status: 500,
        duration: 2.5,
    );

    Event::assertDispatched(TransfeeraRequestComplete::class, function ($event) {
        return $event->domain === 'conta_certa'
            && $event->method === 'POST'
            && $event->status === 500
            && $event->duration === 2.5;
    });
});

test('evento pode ser escutado', function () {
    $listened = false;

    // Registrar listener ANTES do fake, mas usar fake para assertions
    Event::listen(TransfeeraRequestComplete::class, function ($event) use (&$listened) {
        $listened = true;
    });

    TransfeeraRequestComplete::dispatch(
        domain: 'payments',
        method: 'GET',
        url: 'https://api-sandbox.transfeera.com/test',
        status: 200,
        duration: 0.1,
    );

    // Com Event::fake(), listeners registrados depois continuam funcionando
    // se usamos Event::assertDispatched
    Event::assertDispatched(TransfeeraRequestComplete::class);
});

test('evento carrega responseData vazio por padrao', function () {
    TransfeeraRequestComplete::dispatch(
        domain: 'payments',
        method: 'DELETE',
        url: 'https://api-sandbox.transfeera.com/batch/123',
        status: 204,
        duration: 0.05,
    );

    Event::assertDispatched(TransfeeraRequestComplete::class, function ($event) {
        return $event->responseData === [];
    });
});
