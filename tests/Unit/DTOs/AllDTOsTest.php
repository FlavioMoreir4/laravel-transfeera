<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit\DTOs;

use FlavioMoreir4\Transfeera\DTOs\AccountDTO;
use FlavioMoreir4\Transfeera\DTOs\AuthorizationDTO;
use FlavioMoreir4\Transfeera\DTOs\BatchDTO;
use FlavioMoreir4\Transfeera\DTOs\BilletDTO;
use FlavioMoreir4\Transfeera\DTOs\ChargeDTO;
use FlavioMoreir4\Transfeera\DTOs\InfractionAnalysisDTO;
use FlavioMoreir4\Transfeera\DTOs\InfractionBatchAnalysisDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentIntentDTO;
use FlavioMoreir4\Transfeera\DTOs\PaymentLinkDTO;
use FlavioMoreir4\Transfeera\DTOs\PixKeyDTO;
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeDueDTO;
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeImmediateDTO;
use FlavioMoreir4\Transfeera\DTOs\PixQrCodeStaticDTO;
use FlavioMoreir4\Transfeera\DTOs\RecurrenceDTO;
use FlavioMoreir4\Transfeera\DTOs\StatementReportDTO;
use FlavioMoreir4\Transfeera\DTOs\TransferDTO;
use FlavioMoreir4\Transfeera\DTOs\ValidationDTO;

test('AccountDTO toArray filtra campos nulos', function () {
    $dto = new AccountDTO(
        name: 'Empresa XYZ',
        document: '11222333444455',
        email: 'financeiro@xyz.com',
        phone: null,
        tradeName: null,
    );

    expect($dto->toArray())->toBe([
        'name' => 'Empresa XYZ',
        'document' => '11222333444455',
        'email' => 'financeiro@xyz.com',
    ]);
});

test('AuthorizationDTO toArray com todos os campos', function () {
    $dto = new AuthorizationDTO(
        payerPixKey: 'fulano@email.com',
        limitValue: 50000,
        startDate: '2025-01-01',
        endDate: '2025-12-31',
        splitPayment: ['percentage' => 50],
    );

    expect($dto->toArray())->toBe([
        'payer_pix_key' => 'fulano@email.com',
        'limit_value' => 50000,
        'start_date' => '2025-01-01',
        'end_date' => '2025-12-31',
        'split_payment' => ['percentage' => 50],
    ]);
});

test('BatchDTO toArray omite nulos', function () {
    $dto = new BatchDTO('Lote Simples');

    expect($dto->toArray())->toBe(['name' => 'Lote Simples']);
});

test('BilletDTO toArray com campos obrigatórios', function () {
    $dto = new BilletDTO(
        payerName: 'João Silva',
        value: 5000,
        dueDate: '2025-12-31',
        document: '12345678909',
        documentType: 'cpf',
    );

    expect($dto->toArray())->toBe([
        'payer_name' => 'João Silva',
        'value' => 5000,
        'due_date' => '2025-12-31',
        'document' => '12345678909',
        'document_type' => 'cpf',
    ]);
});

test('ChargeDTO toArray filtra nulos', function () {
    $dto = new ChargeDTO(
        payerName: 'João Silva',
        value: 5000,
        payerDocument: null,
        dueDate: '2025-12-31',
    );

    expect($dto->toArray())->toBe([
        'payer_name' => 'João Silva',
        'value' => 5000,
        'due_date' => '2025-12-31',
    ]);
});

test('InfractionAnalysisDTO toArray', function () {
    $dto = new InfractionAnalysisDTO(
        type: 'refund',
        refundAmount: 5000,
        description: 'Devolução por acordo',
    );

    expect($dto->toArray())->toBe([
        'type' => 'refund',
        'refund_amount' => 5000,
        'description' => 'Devolução por acordo',
    ]);
});

test('InfractionBatchAnalysisDTO toArray', function () {
    $dto = new InfractionBatchAnalysisDTO([
        new InfractionAnalysisDTO(type: 'refund', refundAmount: 3000),
        new InfractionAnalysisDTO(type: 'contest', description: 'Pagamento correto'),
    ]);

    expect($dto->toArray())->toBe([
        'analyses' => [
            ['type' => 'refund', 'refund_amount' => 3000],
            ['type' => 'contest', 'description' => 'Pagamento correto'],
        ],
    ]);
});

test('PaymentIntentDTO toArray', function () {
    $dto = new PaymentIntentDTO(
        authorizationId: 'auth_1',
        value: 15000,
        description: 'Mensalidade',
        dueDate: '2025-12-31',
    );

    expect($dto->toArray())->toBe([
        'authorization_id' => 'auth_1',
        'value' => 15000,
        'description' => 'Mensalidade',
        'due_date' => '2025-12-31',
    ]);
});

test('PaymentLinkDTO toArray filtra nulos', function () {
    $dto = new PaymentLinkDTO(
        name: 'Produto X',
        value: 1990,
    );

    expect($dto->toArray())->toBe(['name' => 'Produto X', 'value' => 1990]);
});

test('PixKeyDTO toArray', function () {
    $dto = new PixKeyDTO(type: 'cpf', value: '12345678909');

    expect($dto->toArray())->toBe(['type' => 'cpf', 'value' => '12345678909']);
});

test('PixQrCodeStaticDTO toArray', function () {
    $dto = new PixQrCodeStaticDTO(
        key: 'email@example.com',
        value: 5000,
    );

    expect($dto->toArray())->toBe([
        'key' => 'email@example.com',
        'value' => 5000,
    ]);
});

test('PixQrCodeImmediateDTO toArray', function () {
    $dto = new PixQrCodeImmediateDTO(
        key: 'email@example.com',
        value: 10000,
    );

    expect($dto->toArray())->toBe([
        'key' => 'email@example.com',
        'value' => 10000,
    ]);
});

test('PixQrCodeDueDTO toArray', function () {
    $dto = new PixQrCodeDueDTO(
        key: 'email@example.com',
        value: 10000,
        dueDate: '2025-12-31',
    );

    expect($dto->toArray())->toBe([
        'key' => 'email@example.com',
        'value' => 10000,
        'due_date' => '2025-12-31',
    ]);
});

test('RecurrenceDTO toArray filtra nulos', function () {
    $dto = new RecurrenceDTO(
        name: 'Mensalidade',
        value: 15000,
        pixKey: 'fulano@email.com',
        pixKeyType: 'email',
        startDate: '2025-01-01',
        frequency: 'monthly',
    );

    expect($dto->toArray())->toBe([
        'name' => 'Mensalidade',
        'value' => 15000,
        'pix_key' => 'fulano@email.com',
        'pix_key_type' => 'email',
        'start_date' => '2025-01-01',
        'frequency' => 'monthly',
        'interval' => 1,
    ]);
});

test('StatementReportDTO toArray', function () {
    $dto = new StatementReportDTO(
        startDate: '2025-01-01',
        endDate: '2025-01-31',
    );

    expect($dto->toArray())->toBe([
        'data_inicio' => '2025-01-01',
        'data_fim' => '2025-01-31',
    ]);
});

test('TransferDTO toArray filtra nulos', function () {
    $dto = new TransferDTO(
        amount: 15000,
        pixKey: 'fulano@email.com',
    );

    expect($dto->toArray())->toBe([
        'amount' => 15000,
        'pix_key' => 'fulano@email.com',
    ]);
});

test('ValidationDTO toArray', function () {
    $dto = new ValidationDTO(
        bankCode: '341',
        agency: '1234',
        account: '56789',
        document: '12345678909',
        accountType: 'checking',
    );

    expect($dto->toArray())->toBe([
        'bank_code' => '341',
        'agency' => '1234',
        'account' => '56789',
        'document' => '12345678909',
        'account_type' => 'checking',
    ]);
});
