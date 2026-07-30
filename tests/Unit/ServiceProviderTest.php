<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware;
use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use FlavioMoreir4\Transfeera\TransfeeraClient;
use FlavioMoreir4\Transfeera\TransfeeraServiceProvider;

test('registra o service provider', function () {
    $providers = $this->app->getLoadedProviders();

    expect($providers)->toHaveKey(TransfeeraServiceProvider::class);
    expect($providers[TransfeeraServiceProvider::class])->toBeTrue();
});

test('resolve transfeera client via facade alias', function () {
    $instance = $this->app->make('transfeera');

    expect($instance)->toBeInstanceOf(TransfeeraClient::class);
});

test('resolve transfeera client via class name', function () {
    $instance = $this->app->make(TransfeeraClient::class);

    expect($instance)->toBeInstanceOf(TransfeeraClient::class);
});

test('connector e token manager sao singletons', function () {
    $connector1 = $this->app->make(Connector::class);
    $connector2 = $this->app->make(Connector::class);
    expect($connector1)->toBe($connector2);

    $tm1 = $this->app->make(TokenManager::class);
    $tm2 = $this->app->make(TokenManager::class);
    expect($tm1)->toBe($tm2);
});

test('resolve connector via class name', function () {
    $instance = $this->app->make(Connector::class);

    expect($instance)->toBeInstanceOf(Connector::class);
});

test('resolve token manager via class name', function () {
    $instance = $this->app->make(TokenManager::class);

    expect($instance)->toBeInstanceOf(TokenManager::class);
});

test('resolve mtls configurator via class name', function () {
    $instance = $this->app->make(MtlsConfigurator::class);

    expect($instance)->toBeInstanceOf(MtlsConfigurator::class);
});

test('resolve logging middleware via class name', function () {
    $instance = $this->app->make(LoggingMiddleware::class);

    expect($instance)->toBeInstanceOf(LoggingMiddleware::class);
});

test('resolve metrics middleware via class name', function () {
    $instance = $this->app->make(MetricsMiddleware::class);

    expect($instance)->toBeInstanceOf(MetricsMiddleware::class);
});

test('transfeera client e connector compartilham mesma instancia', function () {
    $client = $this->app->make(TransfeeraClient::class);
    $client2 = $this->app->make(TransfeeraClient::class);

    expect($client)->toBe($client2);

    $connector = $this->app->make(Connector::class);
    $connector2 = $this->app->make(Connector::class);

    expect($connector)->toBe($connector2);
});

test('facade transfeera acessa metodos do client', function () {
    expect(Transfeera::getFacadeRoot())->toBeInstanceOf(TransfeeraClient::class);
});

test('service provider registra alias facade', function () {
    expect($this->app->isAlias(TransfeeraClient::class))->toBeTrue();
    expect($this->app->getAlias(TransfeeraClient::class))->toBe('transfeera');
});
