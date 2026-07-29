<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria instrucao de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents' => Http::response([
            'id' => 'pi_1',
            'status' => 'pending',
            'value' => 15000,
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->create('auth_1', [
        'value' => 15000,
        'description' => 'Pagamento recorrente',
    ]);

    expect($response['id'])->toBe('pi_1');
});

test('lista instrucoes de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents*' => Http::response([
            'data' => [
                ['id' => 'pi_1', 'status' => 'paid'],
                ['id' => 'pi_2', 'status' => 'pending'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->list(['status' => 'pending']);

    expect($response['data'])->toHaveCount(2);
});

test('consulta instrucao de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents/pi_1' => Http::response([
            'id' => 'pi_1',
            'status' => 'paid',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->get('pi_1');

    expect($response['status'])->toBe('paid');
});

test('cancela instrucao de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents/pi_1/cancel' => Http::response([
            'id' => 'pi_1',
            'status' => 'cancelled',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->cancel('pi_1');

    expect($response['status'])->toBe('cancelled');
});

test('consulta cancelamento de instrucao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents/pi_1/cancellation' => Http::response([
            'id' => 'pi_1',
            'cancelled_at' => '2025-06-01T10:00:00Z',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->getCancellation('pi_1');

    expect($response['cancelled_at'])->toBe('2025-06-01T10:00:00Z');
});

test('reenvia retentativa de instrucao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/pix-automatico/payment-intents/pi_1/retry' => Http::response([
            'id' => 'pi_1',
            'status' => 'retry_sent',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_1');

    expect($response['status'])->toBe('retry_sent');
});
