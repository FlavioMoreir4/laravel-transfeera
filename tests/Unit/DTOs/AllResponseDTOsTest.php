<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\DTOs\Response\AccountResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\AuthorizationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BankResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BatchResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BilletCipResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\BilletResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\ChargePdfResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\ChargeResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\InfractionAnalysisResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\InfractionResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PaymentIntentResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PaymentLinkResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixCashInResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixEmvResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixKeyResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixQrCodeResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixRefundResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\RecurrencePaymentResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\RecurrenceResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\StatementReportResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\StatementResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\StatementWithdrawResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\TransferResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\ValidationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\WebhookEventResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\WebhookResponseDTO;

test('AccountResponseDTO createFromApi e toArray', function () {
    $dto = AccountResponseDTO::fromResponse([
        'id' => 'acc_1',
        'name' => 'Empresa XYZ',
        'document' => '11222333444455',
        'status' => 'active',
    ]);

    expect($dto->id)->toBe('acc_1');
    expect($dto->name)->toBe('Empresa XYZ');
    expect($dto->toArray())->toHaveKey('id');
});

test('AuthorizationResponseDTO createFromApi e toArray', function () {
    $dto = AuthorizationResponseDTO::fromResponse([
        'id' => 'auth_1',
        'status' => 'active',
        'payer_pix_key' => 'fulano@email.com',
    ]);

    expect($dto->id)->toBe('auth_1');
    expect($dto->toArray())->toHaveKey('payer_pix_key');
});

test('BankResponseDTO createFromApi e toArray', function () {
    $dto = BankResponseDTO::fromResponse([
        'code' => '341',
        'name' => 'Itaú',
    ]);

    expect($dto->code)->toBe('341');
    expect($dto->name)->toBe('Itaú');
    expect($dto->toArray())->toHaveKey('code');
});

test('BatchResponseDTO createFromApi e toArray', function () {
    $dto = BatchResponseDTO::fromResponse([
        'id' => 'batch_1',
        'status' => 'pending',
        'name' => 'Lote Teste',
        'total_transfers' => 3,
    ]);

    expect($dto->id)->toBe('batch_1');
    expect($dto->totalTransfers)->toBe(3);
    expect($dto->toArray())->toHaveKey('name');
});

test('BilletResponseDTO createFromApi e toArray', function () {
    $dto = BilletResponseDTO::fromResponse([
        'id' => 'bil_1',
        'status' => 'registered',
        'value' => 5000,
        'billet_number' => '001',
        'barcode' => '12345',
        'due_date' => '2025-12-31',
        'beneficiary_name' => 'João',
        'beneficiary_document' => '12345678909',
    ]);

    expect($dto->value)->toBe(5000);
    expect($dto->billetNumber)->toBe('001');
    expect($dto->toArray())->toHaveKey('beneficiary_name');
});

test('ChargeResponseDTO createFromApi e toArray', function () {
    $dto = ChargeResponseDTO::fromResponse([
        'id' => 'ch_1',
        'status' => 'pending',
        'value' => 10000,
        'payer_name' => 'Empresa',
    ]);

    expect($dto->value)->toBe(10000);
    expect($dto->toArray())->toHaveKey('payer_name');
});

test('InfractionResponseDTO createFromApi e toArray', function () {
    $dto = InfractionResponseDTO::fromResponse([
        'id' => 'inf_1',
        'status' => 'open',
        'end_to_end_id' => 'E2E123',
    ]);

    expect($dto->endToEndId)->toBe('E2E123');
    expect($dto->toArray())->toHaveKey('end_to_end_id');
});

test('PaymentIntentResponseDTO createFromApi e toArray', function () {
    $dto = PaymentIntentResponseDTO::fromResponse([
        'id' => 'pi_1',
        'status' => 'active',
        'authorization_id' => 'auth_1',
    ]);

    expect($dto->authorizationId)->toBe('auth_1');
    expect($dto->toArray())->toHaveKey('authorization_id');
});

test('PaymentLinkResponseDTO createFromApi e toArray', function () {
    $dto = PaymentLinkResponseDTO::fromResponse([
        'id' => 'pl_1',
        'status' => 'active',
        'name' => 'Link Produto',
    ]);

    expect($dto->name)->toBe('Link Produto');
    expect($dto->toArray())->toHaveKey('name');
});

test('PixCashInResponseDTO createFromApi e toArray', function () {
    $dto = PixCashInResponseDTO::fromResponse([
        'id' => 'pix_1',
        'status' => 'completed',
        'value' => 50000,
        'payer_name' => 'João',
        'payer_document' => '12345678909',
        'payer_pix_key' => 'joao@email.com',
        'receiver_pix_key' => 'empresa@email.com',
    ]);

    expect($dto->value)->toBe(50000);
    expect($dto->payerName)->toBe('João');
    expect($dto->toArray())->toHaveKey('payer_name');
});

test('PixKeyResponseDTO createFromApi e toArray', function () {
    $dto = PixKeyResponseDTO::fromResponse([
        'id' => 'key_1',
        'value' => 'fulano@email.com',
        'type' => 'email',
    ]);

    expect($dto->value)->toBe('fulano@email.com');
    expect($dto->toArray())->toHaveKey('value');
});

test('PixQrCodeResponseDTO createFromApi e toArray', function () {
    $dto = PixQrCodeResponseDTO::fromResponse([
        'id' => 'qr_1',
        'status' => 'active',
        'value' => 25000,
    ]);

    expect($dto->value)->toBe(25000);
    expect($dto->toArray())->toHaveKey('value');
});

test('PixResponseDTO createFromApi e toArray', function () {
    $dto = PixResponseDTO::fromResponse([
        'key' => 'fulano@email.com',
        'type' => 'email',
        'name' => 'Fulano',
        'document' => '12345678909',
        'bank_code' => '341',
        'bank_name' => 'Itaú',
        'agency' => '1234',
        'account' => '56789',
        'account_type' => 'checking',
        'status' => 'completed',
    ]);

    expect($dto->key)->toBe('fulano@email.com');
    expect($dto->type)->toBe('email');
    expect($dto->toArray())->toHaveKey('key');
});

test('RecurrenceResponseDTO createFromApi e toArray', function () {
    $dto = RecurrenceResponseDTO::fromResponse([
        'id' => 'rec_1',
        'status' => 'active',
        'name' => 'Mensalidade',
        'value' => 15000,
    ]);

    expect($dto->name)->toBe('Mensalidade');
    expect($dto->toArray())->toHaveKey('name');
});

test('StatementResponseDTO createFromApi e toArray', function () {
    $dto = StatementResponseDTO::fromResponse([
        'balance' => 150000,
        'blocked' => 25000,
        'total' => 125000,
    ]);

    expect($dto->balance)->toBe(150000);
    expect($dto->total)->toBe(125000);
    expect($dto->toArray())->toHaveKey('balance');
});

test('StatementWithdrawResponseDTO createFromApi e toArray', function () {
    $dto = StatementWithdrawResponseDTO::fromResponse([
        'id' => 'wd_123',
        'amount' => 50000,
        'status' => 'processing',
        'pix_key' => 'financeiro@empresa.com',
    ]);

    expect($dto->id)->toBe('wd_123');
    expect($dto->amount)->toBe(50000);
    expect($dto->status)->toBe('processing');
    expect($dto->pixKey)->toBe('financeiro@empresa.com');
    expect($dto->toArray())->toHaveKey('pix_key');
});

test('StatementReportResponseDTO createFromApi e toArray', function () {
    $dto = StatementReportResponseDTO::fromResponse([
        'id' => 'rep_1',
        'status' => 'processing',
        'start_date' => '2025-01-01',
        'end_date' => '2025-07-30',
    ]);

    expect($dto->id)->toBe('rep_1');
    expect($dto->status)->toBe('processing');
    expect($dto->startDate)->toBe('2025-01-01');
    expect($dto->endDate)->toBe('2025-07-30');
    expect($dto->toArray())->toHaveKey('start_date');
});

test('TransferResponseDTO createFromApi e toArray', function () {
    $dto = TransferResponseDTO::fromResponse([
        'id' => 'trf_1',
        'status' => 'created',
        'amount' => 5000,
        'pix_key' => 'fulano@email.com',
    ]);

    expect($dto->amount)->toBe(5000);
    expect($dto->toArray())->toHaveKey('pix_key');
});

test('ValidationResponseDTO createFromApi e toArray', function () {
    $dto = ValidationResponseDTO::fromResponse([
        'id' => 'val_1',
        'status' => 'completed',
        'bank_code' => '341',
        'result' => 'approved',
    ]);

    expect($dto->bankCode)->toBe('341');
    expect($dto->result)->toBe('approved');
    expect($dto->toArray())->toHaveKey('bank_code');
});

test('WebhookResponseDTO createFromApi e toArray', function () {
    $dto = WebhookResponseDTO::fromResponse([
        'id' => 'wh_1',
        'status' => 'active',
        'url' => 'https://exemplo.com/webhook',
    ]);

    expect($dto->url)->toBe('https://exemplo.com/webhook');
    expect($dto->toArray())->toHaveKey('url');
});

test('WebhookEventResponseDTO createFromApi e toArray', function () {
    $dto = WebhookEventResponseDTO::fromResponse([
        'id' => 'evt_1',
        'event' => 'batch.processed',
        'status' => 'sent',
    ]);

    expect($dto->event)->toBe('batch.processed');
    expect($dto->toArray())->toHaveKey('event');
});

test('OperationResponseDTO createFromApi e toArray', function () {
    $dto = OperationResponseDTO::fromResponse([
        'success' => true,
        'message' => 'Operação concluída',
    ]);

    expect($dto->success)->toBeTrue();
    expect($dto->message)->toBe('Operação concluída');
    expect($dto->toArray())->toHaveKey('success');
});

test('PixEmvResponseDTO createFromApi e toArray', function () {
    $dto = PixEmvResponseDTO::fromResponse([
        'key' => 'fulano@email.com',
        'value' => 50000,
        'description' => 'Pagamento',
    ]);

    expect($dto->key)->toBe('fulano@email.com');
    expect($dto->value)->toBe(50000);
    expect($dto->toArray())->toHaveKey('key');
});

test('BilletCipResponseDTO createFromApi e toArray', function () {
    $dto = BilletCipResponseDTO::fromResponse([
        'billet_number' => '001',
        'value' => 5000,
        'due_date' => '2025-12-31',
        'cip_status' => 'registered',
    ]);

    expect($dto->billetNumber)->toBe('001');
    expect($dto->cipStatus)->toBe('registered');
    expect($dto->toArray())->toHaveKey('billet_number');
});

test('RecurrencePaymentResponseDTO createFromApi e toArray', function () {
    $dto = RecurrencePaymentResponseDTO::fromResponse([
        'id' => 'rp_1',
        'value' => 15000,
        'due_date' => '2025-08-15',
        'status' => 'pending',
    ]);

    expect($dto->value)->toBe(15000);
    expect($dto->dueDate)->toBe('2025-08-15');
    expect($dto->toArray())->toHaveKey('due_date');
});

test('PixRefundResponseDTO createFromApi e toArray', function () {
    $dto = PixRefundResponseDTO::fromResponse([
        'id' => 'ref_1',
        'amount' => 10000,
        'end_to_end_id' => 'E2E123456',
    ]);

    expect($dto->amount)->toBe(10000);
    expect($dto->endToEndId)->toBe('E2E123456');
    expect($dto->toArray())->toHaveKey('end_to_end_id');
});

test('ChargePdfResponseDTO createFromApi e toArray', function () {
    $dto = ChargePdfResponseDTO::fromResponse([
        'id' => 'ch_1',
        'status' => 'available',
        'url' => 'https://example.com/invoice.pdf',
        'content_type' => 'application/pdf',
        'size' => 12345,
    ]);

    expect($dto->url)->toBe('https://example.com/invoice.pdf');
    expect($dto->contentType)->toBe('application/pdf');
    expect($dto->size)->toBe(12345);
    expect($dto->toArray())->toHaveKey('url');
});

test('InfractionAnalysisResponseDTO createFromApi e toArray', function () {
    $dto = InfractionAnalysisResponseDTO::fromResponse([
        'analysis_id' => 'anl_1',
        'result' => 'approved',
        'infraction_id' => 'inf_1',
    ]);

    expect($dto->analysisId)->toBe('anl_1');
    expect($dto->result)->toBe('approved');
    expect($dto->toArray())->toHaveKey('analysis_id');
});
