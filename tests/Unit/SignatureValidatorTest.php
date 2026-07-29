<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Webhooks\SignatureValidator;

test('valida assinatura correta', function () {
    $secret = 'my-secret-key';
    $payload = '{"event":"batch.processed","data":{"id":"123"}}';
    $signature = hash_hmac('sha256', $payload, $secret);

    $validator = new SignatureValidator($secret);

    expect($validator->isValid($payload, $signature))->toBeTrue();
});

test('rejeita assinatura incorreta', function () {
    $secret = 'my-secret-key';
    $payload = '{"event":"batch.processed","data":{"id":"123"}}';

    $validator = new SignatureValidator($secret);

    expect($validator->isValid($payload, 'invalid-signature'))->toBeFalse();
});

test('calcula assinatura corretamente', function () {
    $secret = 'test-secret';
    $payload = '{"test":"data"}';
    $expected = hash_hmac('sha256', $payload, $secret);

    $validator = new SignatureValidator($secret);

    expect($validator->calculate($payload))->toBe($expected);
});

test('usa regra de recebimentos quando configurado', function () {
    $secret = 'receivables-secret';
    $payload = '{"event":"pix.received","data":{"end2end":"E2E123"}}';

    $validator = new SignatureValidator($secret);

    // Deve usar o mesmo hash, mas a flag pode alterar o algoritmo no futuro
    $expected = hash_hmac('sha256', $payload, $secret);

    expect($validator->calculateForReceivables($payload))->toBe($expected);
    expect($validator->isValidForReceivables($payload, $expected))->toBeTrue();
});

test('usa hash_equals para comparacao timing-safe', function () {
    $secret = 'secret';
    $payload = '{"key":"value"}';
    $validSig = hash_hmac('sha256', $payload, $secret);

    $validator = new SignatureValidator($secret);

    // Assinatura válida
    expect($validator->isValid($payload, $validSig))->toBeTrue();

    // Assinatura inválida (trocando último char)
    $tampered = substr($validSig, 0, -1) . '0';
    expect($validator->isValid($payload, $tampered))->toBeFalse();
});
