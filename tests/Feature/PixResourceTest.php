<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('consulta chave pix no DICT', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/dict_key/11999999999' => Http::response([
            'key' => '11999999999',
            'type' => 'phone',
            'holder_name' => 'João Silva',
            'holder_document' => '***12345609**',
            'bank' => 'Banco do Brasil',
            'bank_code' => '001',
            'agency' => '1234',
            'account' => '56789',
            'account_type' => 'checking',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pix()->lookupKey('11999999999');

    expect($response['key'])->toBe('11999999999');
    expect($response['holder_name'])->toBe('João Silva');
    expect($response['bank'])->toBe('Banco do Brasil');
});

test('consulta chave pix inexistente retorna erro', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/dict_key/00000000000' => Http::response([
            'message' => 'Chave Pix não encontrada',
        ], 404),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::pix()->lookupKey('00000000000'))
        ->toThrow(PaymentException::class, 'Chave Pix não encontrada');
});

test('parseia emv pix copia e cola', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/parse' => Http::response([
            'key' => 'financeiro@empresa.com',
            'value' => 25000,
            'description' => 'Pagamento de produto',
            'transaction_id' => 'tx_abc123',
            'merchant_name' => 'Empresa Ltda',
            'merchant_city' => 'SAO PAULO',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pix()->parseEmv('00020101021226830014br.gov.bcb.pix...');

    expect($response['key'])->toBe('financeiro@empresa.com');
    expect($response['value'])->toBe(25000);
});

test('parseia emv invalido retorna erro de validacao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/parse' => Http::response([
            'message' => 'EMV inválido',
            'errors' => ['emv' => ['Formato inválido']],
        ], 422),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::pix()->parseEmv('invalido'))
        ->toThrow(TransfeeraValidationException::class, 'EMV inválido');
});
