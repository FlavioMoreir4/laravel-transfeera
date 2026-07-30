<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('lista bancos de pagamentos', function () {
    Http::fake([
        'api-sandbox.transfeera.com/bank' => Http::response([
            'data' => [
                ['id' => 'bank_1', 'name' => 'Banco do Brasil', 'code' => '001'],
                ['id' => 'bank_2', 'name' => 'Caixa Econômica', 'code' => '104'],
                ['id' => 'bank_3', 'name' => 'Bradesco', 'code' => '237'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::banks()->list();

    expect($response)->toHaveCount(3);
    expect($response[0]->name)->toBe('Banco do Brasil');
    expect($response[0]->code)->toBe('001');
});

test('lista bancos da conta certa', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/bank' => Http::response([
            'data' => [
                ['id' => 'cb_1', 'name' => 'Itaú', 'code' => '341'],
                ['id' => 'cb_2', 'name' => 'Santander', 'code' => '033'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::contaCertaBanks()->list();

    expect($response)->toHaveCount(2);
    expect($response[0]->name)->toBe('Itaú');
});
