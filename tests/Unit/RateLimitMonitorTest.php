<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Services\RateLimitMonitor;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->monitor = new RateLimitMonitor;
    $this->monitor->clearAll();
});

test('nao armazena estado quando resposta nao tem headers de rate limit', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200)]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    expect($this->monitor->getRemaining('payments'))->toBeNull();
    expect($this->monitor->getLimit('payments'))->toBeNull();
    expect($this->monitor->getReset('payments'))->toBeNull();
    expect($this->monitor->isThrottled('payments'))->toBeFalse();
});

test('armazena headers de rate limit em resposta bem-sucedida', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '87',
        'X-RateLimit-Reset' => '1699000000',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    expect($this->monitor->getRemaining('payments'))->toBe(87);
    expect($this->monitor->getLimit('payments'))->toBe(100);
    expect($this->monitor->getReset('payments'))->toBe(1699000000);
});

test('armazena headers de rate limit em resposta de erro 429', function () {
    Http::fake(['*' => Http::response(['error' => 'rate limit'], 429, [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '0',
        'Retry-After' => '30',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    expect($this->monitor->getRemaining('payments'))->toBe(0);
    expect($this->monitor->getLimit('payments'))->toBe(100);
});

test('detecta throttling quando remaining abaixo do threshold', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '5',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    // 5% restantes de 100 => abaixo de 0.1 (10%)
    expect($this->monitor->isThrottled('payments'))->toBeTrue();
});

test('nao detecta throttling quando remaining acima do threshold', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '20',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    // 20% restantes => acima de 0.1
    expect($this->monitor->isThrottled('payments'))->toBeFalse();
});

test('throttling com threshold customizado', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Limit' => '100',
        'X-RateLimit-Remaining' => '15',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    expect($this->monitor->isThrottled('payments', 0.2))->toBeTrue();  // 15 de 100 < 20%
    expect($this->monitor->isThrottled('payments', 0.1))->toBeFalse(); // 15 de 100 > 10%
});

test('retorna estado completo', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Limit' => '50',
        'X-RateLimit-Remaining' => '30',
    ])]);
    $response = Http::get('https://api.example.com/test');

    $this->monitor->updateFromResponse('payments', $response);

    $state = $this->monitor->getState('payments');

    expect($state['remaining'])->toBe(30);
    expect($state['limit'])->toBe(50);
    expect($state['reset'])->toBeNull();
    expect($state['updated_at'])->toBeInt();
});

test('estado padrao quando dominio nunca consultado', function () {
    $state = $this->monitor->getState('unknown');

    expect($state['remaining'])->toBeNull();
    expect($state['limit'])->toBeNull();
    expect($state['reset'])->toBeNull();
    expect($state['updated_at'])->toBeNull();
});

test('clear remove estado do dominio', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Remaining' => '50',
        'X-RateLimit-Limit' => '100',
    ])]);
    $response = Http::get('https://api.example.com/test');
    $this->monitor->updateFromResponse('payments', $response);

    expect($this->monitor->getRemaining('payments'))->toBe(50);

    $this->monitor->clear('payments');

    expect($this->monitor->getRemaining('payments'))->toBeNull();
});

test('clearAll remove estado de todos os dominios', function () {
    Http::fake(['*' => Http::response(['ok' => true], 200, [
        'X-RateLimit-Remaining' => '80',
        'X-RateLimit-Limit' => '100',
    ])]);
    $response = Http::get('https://api.example.com/test');
    $this->monitor->updateFromResponse('payments', $response);
    $this->monitor->updateFromResponse('conta_certa', $response);

    expect($this->monitor->getRemaining('payments'))->toBe(80);
    expect($this->monitor->getRemaining('conta_certa'))->toBe(80);

    $this->monitor->clearAll();

    expect($this->monitor->getRemaining('payments'))->toBeNull();
    expect($this->monitor->getRemaining('conta_certa'))->toBeNull();
});
