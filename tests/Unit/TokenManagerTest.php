<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('obtem token valido e armazena em cache', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'cached-token',
            'expires_in' => 1800,
        ]),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    $token = $manager->getToken();

    expect($token->token())->toBe('cached-token');
    expect($token->isValid())->toBeTrue();

    // Segunda chamada deve usar cache (sem nova requisicao)
    Http::assertSentCount(1);

    $cachedToken = $manager->getToken();
    expect($cachedToken->token())->toBe('cached-token');

    // Ainda deve ter apenas 1 request
    Http::assertSentCount(1);
});

test('renova token expirado automaticamente', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['access_token' => 'fresh-token', 'expires_in' => 1])    // expires_in=1 → expiresAt no passado
            ->push(['access_token' => 'refreshed-token', 'expires_in' => 1800]),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    // Primeiro token: expires_in=1 → safety margin de 60s faz expiresAt = time() - 59 → já expirado
    // Mas ainda é cacheado, então limpamos manualmente
    $token = $manager->getToken();
    expect($token->token())->toBe('fresh-token');

    // Forca expiracao simulando que o token venceu
    Cache::store('array')->forget('transfeera_access_token');

    // Segunda chamada: usa o sequence (segundo item = 'refreshed-token')
    $refreshed = $manager->getToken();

    expect($refreshed->token())->toBe('refreshed-token');
    Http::assertSentCount(2);
});

test('suporta scope account_id (Hub de Contas)', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => function ($request) {
            $body = $request->body();

            expect($body)->toContain('scope=account_id%3Aacc_123');

            return Http::response([
                'access_token' => 'account-scoped-token',
                'expires_in' => 1800,
            ]);
        },
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    $token = $manager->getToken('acc_123');

    expect($token->token())->toBe('account-scoped-token');
});

test('lanca excecao em falha de autenticacao', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::response(
            ['error' => 'invalid_client'],
            401,
        ),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'invalid',
            'client_secret' => 'invalid',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    expect(fn () => $manager->getToken())
        ->toThrow(TransfeeraAuthenticationException::class);
});

test('limpa cache manualmente', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::response([
            'access_token' => 'token-to-clear',
            'expires_in' => 1800,
        ]),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    $manager->getToken();
    expect(Cache::store('array')->has('transfeera_access_token'))->toBeTrue();

    $manager->clearCache();
    expect(Cache::store('array')->has('transfeera_access_token'))->toBeFalse();
});
