<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria conta digital', function () {
    Http::fake([
        'api-sandbox.transfeera.com/accounts' => Http::response([
            'id' => 'acc_1',
            'name' => 'Empresa XYZ',
            'status' => 'active',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::accounts()->create([
        'name' => 'Empresa XYZ',
        'document' => '11222333444455',
        'email' => 'financeiro@xyz.com',
    ]);

    expect($response['id'])->toBe('acc_1');
    expect($response['status'])->toBe('active');
});

test('lista contas digitais', function () {
    Http::fake([
        'api-sandbox.transfeera.com/accounts*' => Http::response([
            'data' => [
                ['id' => 'acc_1', 'name' => 'Empresa A'],
                ['id' => 'acc_2', 'name' => 'Empresa B'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::accounts()->list();

    expect($response['data'])->toHaveCount(2);
});

test('consulta conta digital', function () {
    Http::fake([
        'api-sandbox.transfeera.com/accounts/acc_1' => Http::response([
            'id' => 'acc_1',
            'name' => 'Empresa XYZ',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::accounts()->get('acc_1');

    expect($response['name'])->toBe('Empresa XYZ');
});

test('encerra conta digital', function () {
    Http::fake([
        'api-sandbox.transfeera.com/accounts/acc_1/close' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::accounts()->close('acc_1');

    expect($response)->toBe([]);
});
