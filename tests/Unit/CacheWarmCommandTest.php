<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Console\Commands\CacheWarmCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
    config()->set('transfeera', [
        'client_id' => 'test-client',
        'client_secret' => 'test-secret',
        'environment' => 'sandbox',
        'cache_store' => 'array',
        'base_urls' => [
            'auth' => [
                'sandbox' => 'https://login-api-sandbox.transfeera.com',
            ],
        ],
    ]);
});

test('comando executa com sucesso sem account-id', function () {
    Http::fake(['*' => Http::response([
        'access_token' => 'cache-warm-token-123',
        'expires_in' => 3600,
    ], 200)]);

    $exitCode = Artisan::call(CacheWarmCommand::class);

    $output = Artisan::output();
    expect($exitCode)->toBe(0);
    expect($output)->toContain('Token padr');
});

test('comando aceita account-id e pre-aquece token multi-tenant', function () {
    Http::fake(['*' => Http::response([
        'access_token' => 'account-token-456',
        'expires_in' => 3600,
    ], 200)]);

    $exitCode = Artisan::call(CacheWarmCommand::class, ['--account-id' => 'acc_789']);

    $output = Artisan::output();
    expect($exitCode)->toBe(0);
    expect($output)->toContain('acc_789');
});

test('comando retorna early quando cache ja valido sem --force', function () {
    $token = AccessToken::fromResponse([
        'access_token' => 'already-cached',
        'expires_in' => 3600,
    ]);
    Cache::put('transfeera_access_token', $token, 3600);

    Http::fake(['*' => Http::response([], 500)]); // should not be called

    $exitCode = Artisan::call(CacheWarmCommand::class);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('cache');
});

test('comando com --force renova mesmo com cache valido', function () {
    $token = AccessToken::fromResponse([
        'access_token' => 'old-token',
        'expires_in' => 3600,
    ]);
    Cache::put('transfeera_access_token', $token, 3600);

    Http::fake(['*' => Http::response([
        'access_token' => 'new-token-after-force',
        'expires_in' => 3600,
    ], 200)]);

    $exitCode = Artisan::call(CacheWarmCommand::class, ['--force' => true]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('cacheado');
});

test('comando retorna erro quando API falha', function () {
    Http::fake(['*' => Http::response([], 500)]);

    $exitCode = Artisan::call(CacheWarmCommand::class);

    expect($exitCode)->toBe(1);
    expect(Artisan::output())->toContain('Falha');
});
