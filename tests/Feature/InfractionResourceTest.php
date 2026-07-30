<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('lista infractions', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions*' => Http::response([
            'data' => [
                ['id' => 'inf_1', 'status' => 'open'],
                ['id' => 'inf_2', 'status' => 'analysed'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::infractions()->list();

    expect($response)->toHaveCount(2);
});

test('consulta infracao por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/inf_1' => Http::response([
            'id' => 'inf_1',
            'status' => 'open',
            'amount' => 15000,
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::infractions()->get('inf_1');

    expect($response->amount)->toBe(15000);
});

test('envia analise individual', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/inf_1/analysis' => Http::response([
            'id' => 'analysis_1',
            'status' => 'submitted',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::infractions()->submitAnalysis('inf_1', [
        'type' => 'refund',
        'refund_amount' => 5000,
        'description' => 'Devolução por acordo',
    ]);

    expect($response['status'])->toBe('submitted');
});

test('envia analise em lote', function () {
    Http::fake([
        'api-sandbox.transfeera.com/med/infractions/analysis' => Http::response([
            'id' => 'batch_1',
            'status' => 'processing',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::infractions()->submitBatchAnalysis([
        ['infraction_id' => 'inf_1', 'type' => 'refund', 'refund_amount' => 5000],
    ]);

    expect($response['status'])->toBe('processing');
});
