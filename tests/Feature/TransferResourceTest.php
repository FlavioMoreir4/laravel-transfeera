<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria transferencia em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123/transfers' => Http::response([
            'id' => 'transfer_1',
            'amount' => 15000,
            'status' => 'pending',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::transfers()->create('batch_123', [
        'amount' => 15000,
        'pix_key' => 'fulano@email.com',
    ]);

    expect($result['id'])->toBe('transfer_1');
    expect($result['amount'])->toBe(15000);
});

test('consulta transferencia', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123/transfers/transfer_1' => Http::response([
            'id' => 'transfer_1',
            'amount' => 15000,
            'status' => 'success',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::transfers()->get('batch_123', 'transfer_1');

    expect($result['status'])->toBe('success');
});

test('lista transferencias de lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123/transfers*' => Http::response([
            'data' => [
                ['id' => 'transfer_1', 'amount' => 10000],
                ['id' => 'transfer_2', 'amount' => 20000],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::transfers()->list('batch_123');

    expect($result['data'])->toHaveCount(2);
});

test('remove transferencia do lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123/transfers/transfer_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::transfers()->delete('batch_123', 'transfer_1');

    expect($result)->toBe([]);
});
