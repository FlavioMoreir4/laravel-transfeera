<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria autorizacao pix automatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations' => Http::response([
            'id' => 'auth_1',
            'status' => 'active',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->create([
        'payer_pix_key' => 'fulano@email.com',
        'limit_value' => 50000,
    ]);

    expect($response['id'])->toBe('auth_1');
    expect($response['status'])->toBe('active');
});

test('lista autorizacoes pix automatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations*' => Http::response([
            'data' => [
                ['id' => 'auth_1', 'status' => 'active'],
                ['id' => 'auth_2', 'status' => 'cancelled'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->list();

    expect($response['data'])->toHaveCount(2);
});

test('consulta autorizacao pix automatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations/auth_1' => Http::response([
            'id' => 'auth_1',
            'status' => 'active',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->get('auth_1');

    expect($response['id'])->toBe('auth_1');
});

test('cancela autorizacao pix automatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations/auth_1/cancel' => Http::response([
            'id' => 'auth_1',
            'status' => 'cancelled',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->cancel('auth_1');

    expect($response['status'])->toBe('cancelled');
});

test('consulta cancelamento de autorizacao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations/auth_1/cancellation' => Http::response([
            'id' => 'auth_1',
            'cancelled_at' => '2025-06-01T10:00:00Z',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->getCancellation('auth_1');

    expect($response['cancelled_at'])->toBe('2025-06-01T10:00:00Z');
});

test('atualiza autorizacao (split_payment)', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/authorizations/auth_1' => Http::response([
            'id' => 'auth_1',
            'split_payment' => ['percentage' => 50],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoAuthorizations()->update('auth_1', [
        'split_payment' => ['percentage' => 50],
    ]);

    expect($response['split_payment']['percentage'])->toBe(50);
});
