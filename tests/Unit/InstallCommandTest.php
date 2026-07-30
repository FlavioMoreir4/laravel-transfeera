<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use Illuminate\Support\Facades\Artisan;

test('register command no artisan', function () {
    $commands = Artisan::all();

    expect($commands)->toHaveKey('transfeera:install');
    expect($commands)->toHaveKey('transfeera:check');
});

test('install command executa sem erros', function () {
    $this->artisan('transfeera:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Instalação concluída');
});

test('install command valida ambiente sandbox', function () {
    $this->app['config']->set('transfeera.environment', 'sandbox');
    $this->app['config']->set('transfeera.client_id', 'test-id');
    $this->app['config']->set('transfeera.client_secret', 'test-secret');

    $this->artisan('transfeera:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Ambiente: sandbox');
});

test('install command valida credenciais faltando', function () {
    $this->app['config']->set('transfeera.client_id', '');
    $this->app['config']->set('transfeera.client_secret', '');

    $this->artisan('transfeera:install')
        ->assertSuccessful()
        ->expectsOutputToContain('Client');
});

test('check command executa sem erros', function () {
    $this->artisan('transfeera:check')
        ->expectsOutputToContain('Verificando Transfeera SDK');
});
