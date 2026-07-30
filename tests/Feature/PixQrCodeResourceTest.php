<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Feature;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use Illuminate\Support\Facades\Http;

test('cria qrcode estatico', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/static' => Http::response([
            'id' => 'qr_static_1',
            'type' => 'static',
            'key' => 'email@example.com',
            'pix_url' => 'pix://...',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->createStatic([
        'key' => 'email@example.com',
    ]);

    expect($response['type'])->toBe('static');
    expect($response['id'])->toBe('qr_static_1');
});

test('cria cobranca imediata', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/collection/immediate' => Http::response([
            'id' => 'qr_imm_1',
            'type' => 'immediate',
            'status' => 'active',
            'value' => 5000,
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->createImmediate([
        'key' => 'email@example.com',
        'value' => 5000,
    ]);

    expect($response['status'])->toBe('active');
});

test('cria cobranca com vencimento', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/collection/dueDate' => Http::response([
            'id' => 'qr_due_1',
            'type' => 'due',
            'status' => 'active',
            'due_date' => '2025-12-31',
        ], 201),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->createDue([
        'key' => 'email@example.com',
        'value' => 10000,
        'due_date' => '2025-12-31',
    ]);

    expect($response['due_date'])->toBe('2025-12-31');
});

test('lista qrcodes', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode*' => Http::response([
            'data' => [
                ['id' => 'qr_1', 'status' => 'active'],
                ['id' => 'qr_2', 'status' => 'active'],
            ],
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->list(['status' => 'active']);

    expect($response)->toHaveCount(2);
});

test('consulta qrcode por id', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/qr_imm_1' => Http::response([
            'id' => 'qr_imm_1',
            'type' => 'immediate',
            'status' => 'active',
            'value' => 5000,
            'key' => 'email@example.com',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->get('qr_imm_1');

    expect($response->id)->toBe('qr_imm_1');
    expect($response->status)->toBe('active');
    expect($response->value)->toBe(5000);
});

test('revoga cobranca', function () {
    Http::fake([
        'api-sandbox.transfeera.com/pix/qrcode/qr_1' => Http::response([
            'id' => 'qr_1',
            'status' => 'revoked',
        ]),
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'test-token',
            'expires_in' => 1800,
        ]),
    ]);

    $response = Transfeera::pixQrCodes()->revoke('qr_1');

    expect($response['status'])->toBe('revoked');
});
