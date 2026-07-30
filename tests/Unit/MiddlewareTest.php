<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware;
use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use FlavioMoreir4\Transfeera\Services\RateLimitMonitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->tokenManager = mock(TokenManager::class);
    $this->tokenManager->shouldReceive('getToken')->andReturn(
        AccessToken::fromResponse(['access_token' => 'test-token', 'expires_in' => 1800])
    );

    $this->mtls = mock(MtlsConfigurator::class);
    $this->mtls->shouldReceive('apply')->andReturnArg(0);

    // Base connector com middlewares desabilitados para testar isoladamente
    $this->connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
    );
});

it('loga request e response quando habilitado', function () {
    $middleware = new LoggingMiddleware(enabled: true, logHeaders: false);

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->once();

    Log::shouldReceive('log')
        ->with('info', \Mockery::type('string'), \Mockery::type('array'))
        ->once();

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        loggingMiddleware: $middleware,
    );

    $result = $connector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

it('usa level error em resposta com erro 5xx', function () {
    $middleware = new LoggingMiddleware(enabled: true);

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['error' => 'Server error'], 500),
    ]);

    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->once();

    Log::shouldReceive('log')
        ->with('error', \Mockery::type('string'), \Mockery::type('array'))
        ->once();

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        loggingMiddleware: $middleware,
    );

    expect(fn () => $connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(PaymentException::class);
});

it('inclui status e duration na resposta logada', function () {
    $middleware = new LoggingMiddleware(enabled: true, logHeaders: true);

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->once();

    Log::shouldReceive('log')
        ->with('info', \Mockery::type('string'), \Mockery::on(function (array $context) {
            return isset($context['status']) && $context['status'] === 200
                && isset($context['duration_ms'])
                && isset($context['request_data']);
        }))
        ->once();

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        loggingMiddleware: $middleware,
    );

    $result = $connector->get(Connector::DOMAIN_PAYMENTS, '/batches', ['page' => 1]);

    expect($result['id'])->toBe('batch_1');
});

it('sanitiza headers sensiveis quando logHeaders ativo', function () {
    $middleware = new LoggingMiddleware(enabled: true, logHeaders: true);

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    Log::shouldReceive('channel')
        ->with('stack')
        ->andReturnSelf()
        ->once();

    Log::shouldReceive('log')
        ->with('info', \Mockery::type('string'), \Mockery::on(function (array $context) {
            return ! isset($context['access_token']);
        }))
        ->once();

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        loggingMiddleware: $middleware,
    );

    $result = $connector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

it('metrica nao interfere em resposta de sucesso quando habilitado', function () {
    $middleware = new MetricsMiddleware(enabled: true, prefix: 'api');

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        metricsMiddleware: $middleware,
    );

    $result = $connector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

it('metrica nao interfere em resposta de erro', function () {
    $middleware = new MetricsMiddleware(enabled: true, prefix: 'api');

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['error' => 'Bad request'], 400),
    ]);

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        metricsMiddleware: $middleware,
    );

    expect(fn () => $connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(PaymentException::class);
});

it('usa prefixo configurado nas metricas', function () {
    $middleware = new MetricsMiddleware(enabled: true, prefix: 'custom_prefix');

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        metricsMiddleware: $middleware,
    );

    expect($middleware->prefix)->toBe('custom_prefix');
    expect($middleware->enabled)->toBeTrue();
});

it('alimenta rate limit monitor em resposta bem-sucedida', function () {
    $rateMonitor = new RateLimitMonitor;
    $middleware = new MetricsMiddleware(enabled: false);

    Http::fake([
        'api-sandbox.transfeera.com/batches*' => Http::response(['id' => 'batch_1'], 200, [
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '88',
            'X-RateLimit-Reset' => '1699000000',
        ]),
    ]);

    $connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
        metricsMiddleware: $middleware,
        rateLimitMonitor: $rateMonitor,
    );

    $result = $connector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
    expect($rateMonitor->getRemaining('payments'))->toBe(88);
    expect($rateMonitor->getLimit('payments'))->toBe(100);
});
