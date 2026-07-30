<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('lanca excecao de validacao em erro 422', function () {
    Cache::forget('transfeera_access_token');
    Cache::forget('transfeera_token_lock');
    Http::stub(null);

    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['name' => ['O campo nome é obrigatório.']],
        ], 422),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::batches()->create([]))
        ->toThrow(TransfeeraValidationException::class);
});

test('cria lote com sucesso', function () {
    $expectedPayload = [
        'id' => 'batch_123',
        'name' => 'Pagamentos Fornecedores',
        'status' => 'pending',
    ];

    Http::fake([
        'api-sandbox.transfeera.com/batch' => Http::response($expectedPayload, 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->create([
        'name' => 'Pagamentos Fornecedores',
    ]);

    expect($result->id)->toBe('batch_123');
    expect($result->name)->toBe('Pagamentos Fornecedores');
    expect($result->status)->toBe('pending');
});

test('consulta lote por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123' => Http::response([
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

    expect($result->id)->toBe('batch_123');
    expect($result->status)->toBe('processed');
});

test('lista lotes com paginacao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch*' => Http::response([
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

    expect($result)->toHaveCount(2);
    expect($result[0]->id)->toBe('batch_1');
    expect($result[1]->id)->toBe('batch_2');
});

test('processa (fecha) lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123/close' => Http::response([
            'id' => 'batch_123',
            'status' => 'processing',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->process('batch_123');

    expect($result->status)->toBe('processing');
});

test('atualiza lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123' => Http::response([
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

    expect($result->name)->toBe('Nome Atualizado');
});

test('remove lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/batch_123' => Http::response([
            'success' => true,
            'message' => 'Lote removido com sucesso',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $result = Transfeera::batches()->delete('batch_123');

    expect($result)->toBeInstanceOf(OperationResponseDTO::class);
    expect($result->success)->toBeTrue();
    expect($result->message)->toBe('Lote removido com sucesso');
});
