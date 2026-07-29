<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria lote com sucesso', function () {
    $expectedPayload = [
        'id' => 'batch_123',
        'name' => 'Pagamentos Fornecedores',
        'status' => 'pending',
    ];

    Http::fake([
        'api-sandbox.transfeera.com/v1/batches' => Http::response($expectedPayload, 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->create([
        'name' => 'Pagamentos Fornecedores',
    ]);

    expect($result)->toBe($expectedPayload);
});

test('consulta lote por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123' => Http::response([
            'id' => 'batch_123',
            'name' => 'Meu Lote',
            'status' => 'processed',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->get('batch_123');

    expect($result['id'])->toBe('batch_123');
    expect($result['status'])->toBe('processed');
});

test('lista lotes com paginacao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches*' => Http::response([
            'data' => [
                ['id' => 'batch_1', 'name' => 'Lote 1'],
                ['id' => 'batch_2', 'name' => 'Lote 2'],
            ],
            'meta' => ['current_page' => 1, 'total' => 2],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->list(['page' => 1, 'per_page' => 10]);

    expect($result['data'])->toHaveCount(2);
});

test('processa (fecha) lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123/process' => Http::response([
            'id' => 'batch_123',
            'status' => 'processing',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->process('batch_123');

    expect($result['status'])->toBe('processing');
});

test('atualiza lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123' => Http::response([
            'id' => 'batch_123',
            'name' => 'Nome Atualizado',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->update('batch_123', [
        'name' => 'Nome Atualizado',
    ]);

    expect($result['name'])->toBe('Nome Atualizado');
});

test('remove lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches/batch_123' => Http::response([], 204),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->delete('batch_123');

    expect($result)->toBe([]);
});

test('lanca excecao de validacao em erro 422', function () {
    Http::fake([
        'api-sandbox.transfeera.com/v1/batches' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['name' => ['O campo nome é obrigatório.']],
        ], 422),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::batches()->create([]))
        ->toThrow(\FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException::class);
});
