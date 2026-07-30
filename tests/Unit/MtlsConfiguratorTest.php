<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use Illuminate\Support\Facades\Http;

test('nao aplica mTLS em sandbox', function () {
    $mtls = new MtlsConfigurator(
        mtlsConfig: ['cert_path' => '', 'key_path' => ''],
        environment: 'sandbox',
    );

    $request = Http::baseUrl('https://example.com');
    $result = $mtls->apply($request);

    expect($result)->toBe($request);
});

test('aplica mTLS em producao com certificado valido', function () {
    // Cria certificados temporarios para o teste
    $certPath = tempnam(sys_get_temp_dir(), 'mtls_test_cert_');
    $keyPath = tempnam(sys_get_temp_dir(), 'mtls_test_key_');
    file_put_contents($certPath, 'fake-cert-content');
    file_put_contents($keyPath, 'fake-key-content');

    $mtls = new MtlsConfigurator(
        mtlsConfig: [
            'cert_path' => $certPath,
            'key_path' => $keyPath,
        ],
        environment: 'production',
    );

    $request = Http::baseUrl('https://example.com');
    $result = $mtls->apply($request);

    expect($result)->not->toBeNull();

    // Limpa
    unlink($certPath);
    unlink($keyPath);
});

test('lanca excecao em producao sem certificado configurado', function () {
    $mtls = new MtlsConfigurator(
        mtlsConfig: ['cert_path' => '', 'key_path' => ''],
        environment: 'production',
    );

    $request = Http::baseUrl('https://example.com');

    expect(fn () => $mtls->apply($request))
        ->toThrow(TransfeeraException::class, 'mTLS é obrigatório em produção');
});

test('lanca excecao se certificado nao existe', function () {
    $mtls = new MtlsConfigurator(
        mtlsConfig: [
            'cert_path' => '/tmp/non-existent-cert.pem',
            'key_path' => '/tmp/non-existent-key.pem',
        ],
        environment: 'production',
    );

    $request = Http::baseUrl('https://example.com');

    expect(fn () => $mtls->apply($request))
        ->toThrow(TransfeeraException::class, 'não encontrado');
});

// ─── mTLS condicional por domínio ────────────────────────────

test('nao aplica mTLS em sandbox mesmo para domínios que exigem', function () {
    $mtls = new MtlsConfigurator(
        mtlsConfig: ['cert_path' => '', 'key_path' => ''],
        environment: 'sandbox',
    );

    // Payments em sandbox não deve exigir mTLS
    $request = Http::baseUrl('https://api-sandbox.transfeera.com');
    $result = $mtls->apply($request);

    expect($result)->toBe($request);
});

test('aplica mTLS em producao para domínio payments', function () {
    $certPath = tempnam(sys_get_temp_dir(), 'mtls_cert_');
    $keyPath = tempnam(sys_get_temp_dir(), 'mtls_key_');
    file_put_contents($certPath, 'cert-content');
    file_put_contents($keyPath, 'key-content');

    $mtls = new MtlsConfigurator(
        mtlsConfig: ['cert_path' => $certPath, 'key_path' => $keyPath],
        environment: 'production',
    );

    $request = Http::baseUrl('https://api.mtls.transfeera.com');
    $result = $mtls->apply($request);

    expect($result)->not->toBeNull();

    unlink($certPath);
    unlink($keyPath);
});

test('aplica mTLS em producao para domínio conta_certa', function () {
    $certPath = tempnam(sys_get_temp_dir(), 'mtls_cc_');
    $keyPath = tempnam(sys_get_temp_dir(), 'mtls_cc_key_');
    file_put_contents($certPath, 'cc-cert');
    file_put_contents($keyPath, 'cc-key');

    $mtls = new MtlsConfigurator(
        mtlsConfig: ['cert_path' => $certPath, 'key_path' => $keyPath],
        environment: 'production',
    );

    $request = Http::baseUrl('https://contacerta-api.mtls.transfeera.com');
    $result = $mtls->apply($request);

    expect($result)->not->toBeNull();

    unlink($certPath);
    unlink($keyPath);
});
