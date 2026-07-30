<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria link de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links' => Http::response([
            'id' => 'pl_1',
            'status' => 'active',
            'name' => 'Produto X',
            'value' => 1990,
            'url' => 'https://pay.transfeera.com/pl_1',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentLinks()->create([
        'name' => 'Produto X',
        'value' => 1990,
    ]);

    expect($response->id)->toBe('pl_1');
    expect($response->name)->toBe('Produto X');
    expect($response->value)->toBe(1990);
    expect($response->status)->toBe('active');
});

test('lista links de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links*' => Http::response([
            'data' => [
                ['id' => 'pl_1', 'status' => 'active', 'name' => 'Produto X', 'value' => 1990],
                ['id' => 'pl_2', 'status' => 'inactive', 'name' => 'Produto Y', 'value' => 2990],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentLinks()->list(['status' => 'active']);

    expect($response)->toHaveCount(2);
    expect($response[0]->id)->toBe('pl_1');
    expect($response[1]->name)->toBe('Produto Y');
});

test('consulta link de pagamento por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links/pl_1' => Http::response([
            'id' => 'pl_1',
            'status' => 'active',
            'name' => 'Produto X',
            'value' => 1990,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentLinks()->get('pl_1');

    expect($response->status)->toBe('active');
    expect($response->name)->toBe('Produto X');
});

test('exclui link de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links/pl_1' => Http::response(['success' => true]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentLinks()->delete('pl_1');

    expect($response->success)->toBeTrue();
});
