<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use Illuminate\Support\Facades\Http;

test('comando transfeera:check existe', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    $this->artisan('transfeera:check --silent')
        ->assertSuccessful();
});

test('sucesso com token valido falso', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    $this->artisan('transfeera:check')
        ->expectsOutputToContain('OK: Credenciais validadas')
        ->assertSuccessful();
});

test('falha com credenciais invalidas', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'error' => 'invalid_client',
            'error_description' => 'Credenciais inválidas',
        ], 401),
    ]);

    $this->artisan('transfeera:check')
        ->assertFailed();
});

test('avisa sobre mTLS em producao sem certificado', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    config()->set('transfeera.environment', 'production');
    config()->set('transfeera.mtls.cert_path', '');
    config()->set('transfeera.mtls.key_path', '');

    $this->artisan('transfeera:check')
        ->expectsOutputToContain('Produção ativa, mas certificado/chave mTLS não configurados')
        ->expectsOutputToContain('OK: Credenciais validadas')
        ->assertSuccessful();
});

test('modo silencioso retorna apenas codigo de saida em sucesso', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'access_token' => 'fake-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ], 200),
    ]);

    $this->artisan('transfeera:check --silent')
        ->assertSuccessful();
});

test('modo silencioso retorna apenas codigo de saida em falha', function () {
    Http::fake([
        '*transfeera.com/authorization' => Http::response([
            'error' => 'invalid_client',
        ], 401),
    ]);

    $this->artisan('transfeera:check --silent')
        ->assertFailed();
});

test('falha quando configuracoes obrigatorias estao ausentes', function () {
    config()->set('transfeera.client_id', '');
    config()->set('transfeera.client_secret', '');

    $this->artisan('transfeera:check')
        ->assertFailed();
});
