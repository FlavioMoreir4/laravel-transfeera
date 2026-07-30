<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

// ─── Payments Webhooks ─────────────────────────────────────

test('payments webhook cria url', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook' => Http::response([
            'id' => 'wh_url_1',
            'url' => 'https://meudominio.com/webhook',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentsWebhooks()->createUrl([
        'url' => 'https://meudominio.com/webhook',
    ]);

    expect($response['url'])->toContain('meudominio.com');
});

test('payments webhook lista urls', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook' => Http::response([
            'data' => [['id' => 'wh_1', 'url' => 'https://...']],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentsWebhooks()->listUrls();

    expect($response['data'])->toHaveCount(1);
});

test('payments webhook lista eventos', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/event*' => Http::response([
            'data' => [
                ['id' => 'evt_1', 'event' => 'batch.processed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentsWebhooks()->listEvents();

    expect($response['data'])->toHaveCount(1);
});

test('payments webhook reenvia evento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/event/evt_1/retry' => Http::response([
            'id' => 'evt_1',
            'status' => 'resent',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentsWebhooks()->resendEvent('evt_1');

    expect($response['status'])->toBe('resent');
});

// ─── Receivables Webhooks ──────────────────────────────────

test('receivables webhook cria url', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook' => Http::response([
            'id' => 'wh_rec_1',
            'url' => 'https://meudominio.com/webhook-recebimentos',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->createUrl([
        'url' => 'https://meudominio.com/webhook-recebimentos',
    ]);

    expect($response['url'])->toContain('webhook-recebimentos');
});

test('receivables webhook consulta url', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/wh_rec_1' => Http::response([
            'id' => 'wh_rec_1',
            'url' => 'https://meudominio.com/webhook-recebimentos',
            'events' => ['charge.paid'],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->getUrl('wh_rec_1');

    expect($response['events'])->toContain('charge.paid');
});

test('receivables webhook lista urls', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook' => Http::response([
            'data' => [
                ['id' => 'wh_rec_1', 'url' => 'https://...'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->listUrls();

    expect($response['data'])->toHaveCount(1);
});

test('receivables webhook atualiza url', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/wh_rec_1' => Http::response([
            'id' => 'wh_rec_1',
            'url' => 'https://novodominio.com/webhook',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->updateUrl('wh_rec_1', [
        'url' => 'https://novodominio.com/webhook',
    ]);

    expect($response['url'])->toContain('novodominio.com');
});

test('receivables webhook deleta url', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/wh_rec_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->deleteUrl('wh_rec_1');

    expect($response)->toBe([]);
});

test('receivables webhook lista eventos', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/event*' => Http::response([
            'data' => [
                ['id' => 'evt_rec_1', 'event' => 'charge.paid'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->listEvents();

    expect($response['data'])->toHaveCount(1);
});

test('receivables webhook reenvia evento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/webhook/event/evt_rec_1/retry' => Http::response([
            'id' => 'evt_rec_1',
            'status' => 'resent',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::receivablesWebhooks()->resendEvent('evt_rec_1');

    expect($response['status'])->toBe('resent');
});

// ─── Conta Certa Webhooks ──────────────────────────────────

test('conta certa webhook cria url', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook' => Http::response([
            'id' => 'wh_cc_1',
            'url' => 'https://meudominio.com/webhook-cc',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->createUrl([
        'url' => 'https://meudominio.com/webhook-cc',
    ]);

    expect($response['url'])->toContain('webhook-cc');
});

test('conta certa webhook consulta url', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook/wh_cc_1' => Http::response([
            'id' => 'wh_cc_1',
            'url' => 'https://meudominio.com/webhook-cc',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->getUrl('wh_cc_1');

    expect($response['url'])->toContain('webhook-cc');
});

test('conta certa webhook lista urls', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook' => Http::response([
            'data' => [
                ['id' => 'wh_cc_1', 'url' => 'https://...'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->listUrls();

    expect($response['data'])->toHaveCount(1);
});

test('conta certa webhook atualiza url', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook/wh_cc_1' => Http::response([
            'id' => 'wh_cc_1',
            'url' => 'https://novodominio.com/webhook-cc',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->updateUrl('wh_cc_1', [
        'url' => 'https://novodominio.com/webhook-cc',
    ]);

    expect($response['url'])->toContain('novodominio.com');
});

test('conta certa webhook deleta url', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook/wh_cc_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->deleteUrl('wh_cc_1');

    expect($response)->toBe([]);
});

test('conta certa webhook reenvia evento', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook/event/evt_cc_1/retry' => Http::response([
            'id' => 'evt_cc_1',
            'status' => 'resent',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->resendEvent('evt_cc_1');

    expect($response['status'])->toBe('resent');
});

test('conta certa webhook lista eventos', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/webhook/event*' => Http::response([
            'data' => [
                ['id' => 'evt_cc_1', 'event' => 'validation.completed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaWebhooks()->listEvents();

    expect($response['data'])->toHaveCount(1);
});
