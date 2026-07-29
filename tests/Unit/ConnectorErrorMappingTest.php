<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->tokenManager = mock(TokenManager::class)
        ->shouldReceive('getToken')
        ->andReturn(new AccessToken('test-token', time() + 3600))
        ->getMock();

    $this->mtls = mock(MtlsConfigurator::class)
        ->shouldReceive('apply')
        ->andReturnArg(0)
        ->getMock();

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
            Connector::DOMAIN_CONTA_CERTA => 'https://contacerta-api-sandbox.transfeera.com',
        ],
    );
});

test('mapeia erro 401 para AuthenticationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Unauthenticated'], 401),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraAuthenticationException::class);
});

test('mapeia erro 422 para ValidationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['name' => ['Campo obrigatório']],
        ], 422),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraValidationException::class);
});

test('mapeia erro 429 para RateLimitException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Too Many Requests'], 429),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraRateLimitException::class);
});
