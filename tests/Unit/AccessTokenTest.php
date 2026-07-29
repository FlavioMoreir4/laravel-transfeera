<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\AccessToken;

test('cria token a partir da resposta da API', function () {
    $token = AccessToken::fromResponse([
        'access_token' => 'eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.test',
        'expires_in' => 1800,
    ]);

    expect($token->token())->toBe('eyJhbGciOiJIUzI1NiJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.test');
    expect($token->expiresAt())->toBeGreaterThan(time());
    expect($token->isValid())->toBeTrue();
    expect($token->isExpired())->toBeFalse();
});

test('token expirado é detectado corretamente', function () {
    $token = new AccessToken(
        token: 'test-token',
        expiresAt: time() - 10,
    );

    expect($token->isValid())->toBeFalse();
    expect($token->isExpired())->toBeTrue();
});

test('aplica margem de segurança de 60 segundos', function () {
    $token = AccessToken::fromResponse([
        'access_token' => 'test-token',
        'expires_in' => 10,
    ]);

    // expires_in=10, margem=60 → token já nasce expirado
    expect($token->isExpired())->toBeTrue();
});
