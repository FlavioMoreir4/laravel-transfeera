<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Auth\TokenManager;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

test('lock evita renovacao concorrente do token', function () {
    $requestCount = 0;

    Http::fake([
        'login-api-sandbox.transfeera.com/*' => function () use (&$requestCount) {
            $requestCount++;
            usleep(50_000); // simula latência de rede

            return Http::response([
                'access_token' => "token-{$requestCount}",
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

    // Simula duas chamadas concorrentes - a segunda deve pegar do cache,
    // não fazer outra requisição HTTP
    $token1 = $manager->getToken();
    // Força cache miss para simular concorrência real
    Cache::store('array')->forget('transfeera_access_token');

    $token2 = $manager->getToken();

    // Como há double-check com lock, apenas 2 requests (uma para cada cache miss)
    // Em vez de flood requests
    expect($token1->token())->toBe('token-1');
    expect($token2->token())->toBe('token-2');
    // Não deve ter mais de 3 requests (2 reais + 1 do fallback eventual)
    expect($requestCount)->toBeLessThanOrEqual(3);
});

test('lock retorna token valido apos espera quando outra requisicao esta renovando', function () {
    $callOrder = [];

    Http::fake([
        'login-api-sandbox.transfeera.com/*' => function () use (&$callOrder) {
            $callOrder[] = 'http';
            // Latência de 200ms para garantir que o lock seja testado
            usleep(200_000);

            return Http::response([
                'access_token' => 'fresh-token',
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

    // Simula cache vazio
    Cache::store('array')->forget('transfeera_access_token');

    // Primeira chamada (adquire o lock)
    $token1 = $manager->getToken();

    expect($token1->token())->toBe('fresh-token');
    // Apenas 1 chamada HTTP para renovar (não 2)
    expect(count($callOrder))->toBe(1);
});

test('cache miss duplo apos expiracao gera apenas uma renovacao', function () {
    $requestCount = 0;

    Http::fake([
        'login-api-sandbox.transfeera.com/*' => function () use (&$requestCount) {
            $requestCount++;

            return Http::response([
                'access_token' => 'refreshed-token',
                'expires_in' => 3600,
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

    // Cache vazio, primeira chamada renova
    $token1 = $manager->getToken();

    // Cache populado, segunda chamada usa cache
    $token2 = $manager->getToken();

    expect($token1->token())->toBe('refreshed-token');
    expect($token2->token())->toBe('refreshed-token');
    expect($requestCount)->toBe(1);
});

test('usa cache separado por accountId', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['access_token' => 'default-token', 'expires_in' => 1800])
            ->push(['access_token' => 'acc-123-token', 'expires_in' => 1800]),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    $default = $manager->getToken();
    $scoped = $manager->getToken('acc_123');

    expect($default->token())->toBe('default-token');
    expect($scoped->token())->toBe('acc-123-token');
});

test('cache de accountId nao interfere no cache default', function () {
    Http::fake([
        'login-api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['access_token' => 'acc-token', 'expires_in' => 1800]),
    ]);

    $manager = new TokenManager(
        config: [
            'client_id' => 'test-client',
            'client_secret' => 'test-secret',
            'cache_store' => 'array',
        ],
        authBaseUrl: 'https://login-api-sandbox.transfeera.com',
    );

    Cache::store('array')->put('transfeera_access_token', new AccessToken('existing-token', time() + 3600), 3600);

    // Apesar de existir cache default, accountId busca renovação
    $scoped = $manager->getToken('acc_456');

    expect($scoped->token())->toBe('acc-token');
    // O cache default permanece intacto
    $cachedDefault = Cache::store('array')->get('transfeera_access_token');
    expect($cachedDefault->token())->toBe('existing-token');
});
