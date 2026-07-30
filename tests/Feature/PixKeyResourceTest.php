<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('lista chaves pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key' => Http::response([
            'data' => [
                ['id' => 'key_1', 'type' => 'cpf', 'value' => '***12345609**', 'status' => 'verified'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->list();

    expect($response)->toHaveCount(1);
    expect($response[0]->type)->toBe('cpf');
});

test('cria chave pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key' => Http::response([
            'id' => 'key_abc123',
            'type' => 'email',
            'value' => 'user@example.com',
            'status' => 'pending',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->create([
        'type' => 'email',
        'value' => 'user@example.com',
    ]);

    expect($response->id)->toBe('key_abc123');
    expect($response->status)->toBe('pending');
});

test('verifica chave pix com codigo', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key/key_abc/verify' => Http::response([
            'id' => 'key_abc',
            'status' => 'verified',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->verify('key_abc', '123456');

    expect($response->status)->toBe('verified');
});

test('remove chave pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key/key_abc' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->delete('key_abc');

    expect($response)->toBeInstanceOf(OperationResponseDTO::class);
    Http::assertSent(fn ($request) => $request->method() === 'DELETE');
});

test('consulta chave pix por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key/key_1' => Http::response([
            'id' => 'key_1',
            'type' => 'phone',
            'value' => '+551****9999',
            'status' => 'verified',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->get('key_1');

    expect($response->id)->toBe('key_1');
});

test('portabilidade: claim, confirm, cancel', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key/1199999999/claim' => Http::response([
            'id' => 'claim_1',
            'status' => 'pending',
        ], 201),
        'api-sandbox.transfeera.com/pix/key/claim_1/claim/confirm' => Http::response([
            'id' => 'claim_1',
            'status' => 'confirmed',
        ]),
        'api-sandbox.transfeera.com/pix/key/claim_1/claim/cancel' => Http::response([
            'id' => 'claim_1',
            'status' => 'cancelled',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $claim = Transfeera::pixKeys()->claim('1199999999');
    expect($claim->status)->toBe('pending');

    $confirmed = Transfeera::pixKeys()->confirmClaim('claim_1');
    expect($confirmed->status)->toBe('confirmed');

    $cancelled = Transfeera::pixKeys()->cancelClaim('claim_1');
    expect($cancelled->status)->toBe('cancelled');
});

test('reenvia codigo de verificacao de chave pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/key/key_abc/resendVerificationCode' => Http::response([
            'id' => 'key_abc',
            'status' => 'pending',
            'message' => 'Código reenviado com sucesso',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixKeys()->resendVerificationCode('key_abc');

    expect($response->message)->toContain('reenviado');
});
