<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria cobranca', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges' => Http::response([
            'id' => 'chg_1',
            'status' => 'pending',
            'value' => 5000,
            'boleto_url' => 'https://...',
            'pix_qr_code' => 'pix://...',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::charges()->create([
        'payer_name' => 'João Silva',
        'value' => 5000,
    ]);

    expect($response['id'])->toBe('chg_1');
    expect($response['status'])->toBe('pending');
});

test('lista cobrancas', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges*' => Http::response([
            'data' => [
                ['id' => 'chg_1', 'status' => 'pending'],
                ['id' => 'chg_2', 'status' => 'paid'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::charges()->list(['status' => 'pending']);

    expect($response['data'])->toHaveCount(2);
});

test('consulta cobranca por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges/chg_1' => Http::response([
            'id' => 'chg_1',
            'status' => 'paid',
            'value' => 5000,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::charges()->get('chg_1');

    expect($response['status'])->toBe('paid');
});

test('cancela cobranca', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges/chg_1/cancel' => Http::response([
            'id' => 'chg_1',
            'status' => 'cancelled',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::charges()->cancel('chg_1');

    expect($response['status'])->toBe('cancelled');
});

test('download pdf comprovante', function () {
    Http::fake([
        'api-sandbox.transfeera.com/charges/chg_1/receivables/rec_1/pdf' => Http::response([
            'url' => 'https://.../comprovante.pdf',
        ]),
        'api-sandbox.transfeera.com/charges/chg_2/pdf' => Http::response([
            'url' => 'https://.../comprovante-legacy.pdf',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::charges()->downloadPdf('chg_1', 'rec_1');
    expect($response['url'])->toContain('.pdf');

    $legacy = Transfeera::charges()->downloadPdfByChargeId('chg_2');
    expect($legacy['url'])->toContain('.pdf');
});
