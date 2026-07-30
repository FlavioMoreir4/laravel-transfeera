<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::store('array')->forget('transfeera_access_token');
});

test('consulta saldo', function () {
    Http::fake([
        'api-sandbox.transfeera.com/statement/balance' => Http::response([
            'balance' => 150000,
            'blocked' => 25000,
            'total' => 125000,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::statement()->getBalance();

    expect($response->balance)->toBe(150000);
    expect($response->total)->toBe(125000);
});

test('resgata saldo para conta bancaria', function () {
    Http::fake([
        'api-sandbox.transfeera.com/statement/withdraw' => Http::response([
            'id' => 'wd_123',
            'amount' => 50000,
            'status' => 'processing',
            'pix_key' => 'financeiro@empresa.com',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::statement()->withdraw([
        'amount' => 50000,
        'pix_key' => 'financeiro@empresa.com',
    ]);

    expect($response['id'])->toBe('wd_123');
    expect($response['amount'])->toBe(50000);
});

test('solicita relatorio de extrato', function () {
    Http::fake([
        'api-sandbox.transfeera.com/statement_report' => Http::response([
            'id' => 'rep_1',
            'status' => 'processing',
            'start_date' => '2025-01-01',
            'end_date' => '2025-07-30',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::statement()->requestReport([
        'start_date' => '2025-01-01',
        'end_date' => '2025-07-30',
    ]);

    expect($response['id'])->toBe('rep_1');
    expect($response['status'])->toBe('processing');
});

test('consulta relatorio de extrato por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/statement_report/rep_1' => Http::response([
            'id' => 'rep_1',
            'status' => 'completed',
            'url' => 'https://api.transfeera.com/reports/rep_1.csv',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::statement()->getReport('rep_1');

    expect($response['status'])->toBe('completed');
    expect($response['url'])->toContain('reports/rep_1');
});

test('resgata saldo com valor zerado retorna erro', function () {
    Http::fake([
        'api-sandbox.transfeera.com/statement/withdraw' => Http::response([
            'message' => 'Saldo insuficiente para resgate',
        ], 400),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    expect(fn () => Transfeera::statement()->withdraw(['amount' => 0, 'pix_key' => 'test@test.com']))
        ->toThrow(PaymentException::class, 'Saldo insuficiente para resgate');
});
