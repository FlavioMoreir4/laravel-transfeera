<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria link de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links' => Http::response([
            'id' => 'pl_1',
            'name' => 'Produto X',
            'value' => 1990,
            'url' => 'https://pay.transfeera.com/pl_1',
            'status' => 'active',
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
    expect($response->status)->toBe('active');
});

test('consulta link de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links/pl_1' => Http::response([
            'id' => 'pl_1',
            'status' => 'active',
            'value' => 1990,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::paymentLinks()->get('pl_1');

    expect($response->status)->toBe('active');
});

test('exclui link de pagamento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/payment_links/pl_1' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    Transfeera::paymentLinks()->delete('pl_1');

    Http::assertSent(fn ($request) => $request->method() === 'DELETE');
});
