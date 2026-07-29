<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('conta certa cria validacao', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/validation' => Http::response([
            'id' => 'val_1',
            'status' => 'pending',
            'bank_code' => '341',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaValidations()->create([
        'bank_code' => '341',
        'agency' => '1234',
        'account' => '56789',
        'document' => '12345678909',
        'account_type' => 'checking',
    ]);

    expect($response['id'])->toBe('val_1');
    expect($response['status'])->toBe('pending');
});

test('conta certa lista validacoes', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/validation*' => Http::response([
            'data' => [
                ['id' => 'val_1', 'status' => 'completed'],
                ['id' => 'val_2', 'status' => 'failed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaValidations()->list();

    expect($response['data'])->toHaveCount(2);
});

test('conta certa consulta validacao', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/validation/val_1' => Http::response([
            'id' => 'val_1',
            'status' => 'completed',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaValidations()->get('val_1');

    expect($response['status'])->toBe('completed');
});

test('conta certa lista bancos', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/bank' => Http::response([
            'data' => [
                ['code' => '341', 'name' => 'Itaú'],
                ['code' => '001', 'name' => 'Banco do Brasil'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaBanks()->list();

    expect($response['data'])->toHaveCount(2);
});
