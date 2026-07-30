<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::store('array')->forget('transfeera_access_token');
});

test('lista recorrências', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payout_recurrences*' => Http::response([
            'data' => [
                ['id' => 'rec_1', 'name' => 'Aluguel', 'status' => 'active', 'value' => 300000],
                ['id' => 'rec_2', 'name' => 'Assinatura', 'status' => 'active', 'value' => 5990],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::recurrences()->list(['status' => 'active']);

    expect($response)->toHaveCount(2);
    expect($response[0]->name)->toBe('Aluguel');
});

test('lista pagamentos de uma recorrência', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payout_recurrences/rec_1/payments*' => Http::response([
            'data' => [
                ['id' => 'pout_1', 'value' => 300000, 'status' => 'completed', 'scheduled_date' => '2025-01-15'],
                ['id' => 'pout_2', 'value' => 300000, 'status' => 'pending', 'scheduled_date' => '2025-02-15'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::recurrences()->listPayments('rec_1', ['page' => 1, 'per_page' => 10]);

    expect($response['data'])->toHaveCount(2);
    expect($response['data'][0]['status'])->toBe('completed');
});

test('cancela recorrência ativa', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payout_recurrences/rec_1/cancel' => Http::response([
            'id' => 'rec_1',
            'status' => 'cancelled',
            'cancelled_at' => '2025-07-30T10:00:00Z',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::recurrences()->cancel('rec_1');

    expect($response['status'])->toBe('cancelled');
});

test('tenta cancelar recorrência já cancelada', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payout_recurrences/rec_2/cancel' => Http::response([
            'message' => 'Recorrência já está cancelada',
        ], 400),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::recurrences()->cancel('rec_2'))
        ->toThrow(PaymentException::class, 'Recorrência já está cancelada');
});
