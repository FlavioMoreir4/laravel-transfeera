<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Console\Commands\DebugCommand;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    config()->set('transfeera', [
        'environment' => 'sandbox',
        'client_id' => 'test-client-id-12345',
        'client_secret' => 'test-secret-value',
        'timeout' => 30,
        'retry' => ['max_attempts' => 3, 'delay_ms' => 100],
        'cache_store' => 'file',
        'user_agent' => 'Laravel App (test@example.com)',
        'logging' => ['enabled' => true],
        'metrics' => ['enabled' => false],
        'mtls' => ['cert_path' => '', 'key_path' => ''],
        'base_urls' => [],
    ]);
});

test('comando retorna sucesso', function () {
    $exitCode = Artisan::call(DebugCommand::class);

    expect($exitCode)->toBe(0);
});

test('comando exibe informacoes do ambiente', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)
        ->toContain('Transfeera SDK')
        ->toContain('Ambiente')
        ->toContain('Configuração');
});

test('comando exibe environment configurado', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)->toContain('sandbox');
});

test('comando exibe client id mascarado', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)
        ->toContain('test')
        ->toContain('*****');
});

test('comando exibe status do token', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)->toContain('Token');
});

test('comando exibe URLs base', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)
        ->toContain('Autenticação')
        ->toContain('Pagamentos')
        ->toContain('Conta Certa');
});

test('comando exibe resources disponiveis', function () {
    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)
        ->toContain('Resources Disponíveis')
        ->toContain('Lotes')
        ->toContain('Chaves Pix')
        ->toContain('Webhooks');
});

test('comando detailed nao quebra', function () {
    $exitCode = Artisan::call(DebugCommand::class, ['--detailed' => true]);

    expect($exitCode)->toBe(0);
});

test('comando com producao sem mtls avisa', function () {
    config()->set('transfeera.environment', 'production');

    Artisan::call(DebugCommand::class);
    $output = Artisan::output();

    expect($output)->toContain('não configurado');
});
