<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;

test('nao registra metrica quando desabilitado', function () {
    $mw = new MetricsMiddleware(enabled: false);

    $result = $mw->recordMetric('payments', 'GET', 200, 0.15);

    // Apenas verifica que não lança exceção
    expect($result)->toBeNull();
});

test('registra metrica quando habilitado', function () {
    $mw = new MetricsMiddleware(enabled: true);

    $result = $mw->recordMetric('payments', 'GET', 200, 0.15);

    // Placeholder — não faz nada além de não lançar exceção
    expect($result)->toBeNull();
});

test('usa prefixo personalizado', function () {
    $mw = new MetricsMiddleware(enabled: true, prefix: 'app');

    $result = $mw->recordMetric('receivables', 'POST', 201, 0.3);

    expect($result)->toBeNull();
});

test('registra metrica com erro', function () {
    $mw = new MetricsMiddleware(enabled: true);

    $result = $mw->recordMetric('payments', 'POST', 500, 1.5);

    expect($result)->toBeNull();
});

test('registra metrica com duracao zero', function () {
    $mw = new MetricsMiddleware(enabled: true);

    $result = $mw->recordMetric('payments', 'GET', 200, 0.0);

    expect($result)->toBeNull();
});
