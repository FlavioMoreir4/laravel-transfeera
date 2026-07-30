<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\DTOs\Response\PixRefundResponseDTO;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('consulta pix recebidos por periodo', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/cashin*' => Http::response([
            'data' => [
                ['end2end_id' => 'E2E123', 'value' => 15000, 'status' => 'completed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixCashIn()->list([
        'start_date' => '2025-01-01',
        'end_date' => '2025-01-31',
    ]);

    expect($response)->toHaveCount(1);
    expect($response[0]->value)->toBe(15000);
});

test('consulta pix por end2endId', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/cashin/E2E123' => Http::response([
            'end2end_id' => 'E2E123',
            'value' => 15000,
            'status' => 'completed',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixCashIn()->getByEnd2EndId('E2E123');

    expect($response->id)->toBe('E2E123');
});

test('solicita devolucao de pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/cashin/E2E123/refund' => Http::response([
            'refund_id' => 'ref_abc',
            'amount' => 5000,
            'status' => 'pending',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixCashIn()->requestRefund('E2E123', [
        'amount' => 5000,
        'description' => 'Devolução total',
    ]);

    expect($response)->toBeInstanceOf(PixRefundResponseDTO::class);
    expect($response->amount)->toBe(5000);
    expect($response->status)->toBe('pending');
});

test('consulta devolucoes de um pix', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/cashin/E2E123/refund' => Http::response([
            'data' => [
                ['refund_id' => 'ref_1', 'amount' => 5000, 'status' => 'completed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixCashIn()->getRefunds('E2E123');

    expect($response)->toHaveCount(1);
    expect($response[0])->toBeInstanceOf(PixRefundResponseDTO::class);
    expect($response[0]->amount)->toBe(5000);
});
