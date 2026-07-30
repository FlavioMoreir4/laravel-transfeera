<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::forget('transfeera_access_token');
    Cache::forget('transfeera_token_lock');
});

test('cria boleto em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/billet' => Http::response([
            'id' => 'blt_1',
            'status' => 'pending',
            'value' => 15000,
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->create('batch_123', [
        'barcode' => '12345678901234567890123456789012345678901234',
        'value' => 15000,
    ]);

    expect($response->id)->toBe('blt_1');
    expect($response->value)->toBe(15000);
});

test('cria boleto avulso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/billet' => Http::response([
            'id' => 'blt_2',
            'status' => 'pending',
            'value' => 20000,
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->createStandalone([
        'barcode' => '12345678901234567890123456789012345678901235',
        'value' => 20000,
    ]);

    expect($response->id)->toBe('blt_2');
});

test('atualiza boleto em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/billet/blt_1' => Http::response([
            'id' => 'blt_1',
            'value' => 16000,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->update('batch_123', 'blt_1', [
        'value' => 16000,
    ]);

    expect($response->value)->toBe(16000);
});

test('atualiza boleto avulso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/billet/blt_2' => Http::response([
            'id' => 'blt_2',
            'value' => 21000,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->updateStandalone('blt_2', [
        'value' => 21000,
    ]);

    expect($response->value)->toBe(21000);
});

test('consulta boleto', function () {
    Http::fake([
        'api-sandbox.transfeera.com/billet/blt_1' => Http::response([
            'id' => 'blt_1',
            'status' => 'paid',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->get('blt_1');

    expect($response->status)->toBe('paid');
});

test('lista boletos em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/billet*' => Http::response([
            'data' => [
                ['id' => 'blt_1', 'status' => 'paid'],
                ['id' => 'blt_2', 'status' => 'pending'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->list('batch_123');

    expect($response)->toHaveCount(2);
});

test('lista boletos avulsos', function () {
    Http::fake([
        'api-sandbox.transfeera.com/billet*' => Http::response([
            'data' => [
                ['id' => 'blt_1', 'status' => 'paid'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->listStandalone();

    expect($response)->toHaveCount(1);
});

test('remove boleto de lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/billet/blt_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->delete('batch_123', 'blt_1');

    expect($response)->toBe([]);
});

test('remove boleto avulso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/billet/blt_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->deleteStandalone('blt_1');

    expect($response)->toBe([]);
});

test('consulta situacao na cip', function () {
    Cache::forget('transfeera_access_token');
    Cache::forget('transfeera_token_lock');

    Http::fake([
        'api-sandbox.transfeera.com/billet/consult*' => Http::response([
            'id' => 'blt_1',
            'status' => 'registered',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::billets()->consultCip('blt_1');

    expect($response['status'])->toBe('registered');
});
