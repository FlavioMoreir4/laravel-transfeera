<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

test('nao loga quando desabilitado', function () {
    Log::shouldReceive('channel')->never();
    $mw = new LoggingMiddleware(enabled: false);
    $mw->log('GET', 'https://api.example.com/test', [], null, 0.1);
});

test('loga mensagem basica com sucesso', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            $level === 'info'
            && str_contains($message, 'Transfeera API GET')
            && str_contains($message, '200')
            && $context['duration_ms'] === 150.0
        ));

    $mw = new LoggingMiddleware;
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('GET', 'https://api.example.com/test', [], $response, 0.15);
});

test('altera nivel para warning em erro 4xx', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level) => $level === 'warning');

    $mw = new LoggingMiddleware;
    Http::fake(['*' => Http::response(['error' => 'bad request'], 400)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('POST', 'https://api.example.com/test', [], $response, 0.1);
});

test('altera nivel para error em erro 5xx', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level) => $level === 'error');

    $mw = new LoggingMiddleware;
    Http::fake(['*' => Http::response(['error' => 'server error'], 500)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('POST', 'https://api.example.com/test', [], $response, 0.1);
});

test('usa nivel por dominio quando configurado', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level) => $level === 'debug');

    $mw = new LoggingMiddleware(levelByDomain: ['conta_certa' => 'debug']);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://contacerta-api.example.com/conta-certa/validation/123');

    $mw->log('GET', 'https://contacerta-api.example.com/conta-certa/validation/123', [], $response, 0.1);
});

test('usa nivel por status quando configurado', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level) => $level === 'debug');

    $mw = new LoggingMiddleware(levelByStatus: [200 => 'debug']);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('GET', 'https://api.example.com/test', [], $response, 0.1);
});

test('nao loga quando nivel none', function () {
    Log::shouldReceive('channel')->never();
    Log::shouldReceive('log')->never();

    $mw = new LoggingMiddleware(levelByStatus: [200 => 'none']);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('GET', 'https://api.example.com/test', [], $response, 0.1);
});

test('sanitiza dados sensiveis no request', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            $context['request_data']['client_id'] === '***'
            && $context['request_data']['client_secret'] === '***'
            && $context['request_data']['name'] === 'João'
        ));

    $mw = new LoggingMiddleware(logHeaders: true);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('POST', 'https://api.example.com/test', [
        'client_id' => 'secret-123',
        'client_secret' => 'super-secret-value',
        'name' => 'João',
    ], $response, 0.1);
});

test('mascara campos bancarios parcialmente', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            str_starts_with((string) $context['request_data']['document'], '12')
            && str_ends_with((string) $context['request_data']['document'], '09')
            && str_contains((string) $context['request_data']['document'], '*****')
        ));

    $mw = new LoggingMiddleware(logHeaders: true);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('POST', 'https://api.example.com/test', [
        'document' => '12345678909',
        'account' => '56789-0',
    ], $response, 0.1);
});

test('trunca payloads grandes', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            isset($context['request_data']['_truncated'])
            && $context['request_data']['_truncated'] === true
        ));

    $mw = new LoggingMiddleware(logHeaders: true, maxBodyLength: 50);
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $largeData = ['data' => str_repeat('x', 500)];
    $mw->log('POST', 'https://api.example.com/test', $largeData, $response, 0.1);
});

test('inclui response body quando configurado', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            $context['response_data']['status'] === 'processed'
        ));

    $mw = new LoggingMiddleware(logResponseBody: true);
    Http::fake(['*' => Http::response(['status' => 'processed', 'id' => 'abc'], 200)]);
    $response = Http::get('https://api.example.com/test');

    $mw->log('GET', 'https://api.example.com/test', [], $response, 0.1);
});

test('extrai dominio conta_certa da url', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            $context['domain'] === 'conta_certa'
        ));

    $mw = new LoggingMiddleware;
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api-sandbox.transfeera.com/conta-certa/validation/123');

    $mw->log('GET', 'https://api-sandbox.transfeera.com/conta-certa/validation/123', [], $response, 0.1);
});

test('extrai dominio infractions da url', function () {
    Log::shouldReceive('channel')
        ->once()
        ->with('stack')
        ->andReturnSelf();
    Log::shouldReceive('log')
        ->once()
        ->withArgs(fn (string $level, string $message, array $context) => (
            $context['domain'] === 'infractions'
        ));

    $mw = new LoggingMiddleware;
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api-sandbox.transfeera.com/med/infractions');

    $mw->log('GET', 'https://api-sandbox.transfeera.com/med/infractions', [], $response, 0.1);
});
