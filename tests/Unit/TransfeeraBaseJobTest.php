<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Jobs\TransfeeraBaseJob;
use Illuminate\Support\Facades\Queue;

/**
 * Job concreto para teste que expõe métodos protegidos.
 */
class TestJob extends TransfeeraBaseJob
{
    public bool $handled = false;

    public function handle(): void
    {
        $this->handled = true;
    }

    // Expõe métodos protegidos para teste
    public function exposeLogInfo(string $message): void
    {
        $this->logInfo($message);
    }

    public function exposeLogWarning(string $message): void
    {
        $this->logWarning($message);
    }

    public function exposeLogError(string $message): void
    {
        $this->logError($message);
    }
}

/**
 * Job que falha intencionalmente.
 */
class FailingTestJob extends TransfeeraBaseJob
{
    public function handle(): void
    {
        throw new RuntimeException('Falha intencional');
    }
}

beforeEach(function () {
    Queue::fake();
});

test('pode ser dispatchado', function () {
    TestJob::dispatch(['batch_id' => '123'], 'payments');

    Queue::assertPushed(TestJob::class, function ($job) {
        return $job->data === ['batch_id' => '123']
            && $job->domain === 'payments';
    });
});

test('usa dominio padrao payments quando nao especificado', function () {
    TestJob::dispatch([]);

    Queue::assertPushed(TestJob::class, function ($job) {
        return $job->domain === 'payments';
    });
});

test('pode ser processado com handle', function () {
    $job = new TestJob(['data' => 'value'], 'payments');
    $job->handle();

    expect($job->handled)->toBeTrue();
});

test('backoff retorna array com 5 valores', function () {
    $job = new TestJob([], 'payments');

    $backoff = $job->backoff();

    expect($backoff)->toBeArray();
    expect($backoff)->toHaveCount(5);
});

test('backoff padrao tem valores crescentes', function () {
    $job = new TestJob([], 'payments');

    $backoff = $job->backoff();

    expect($backoff[0])->toBe(5);
    expect($backoff[1])->toBeGreaterThan($backoff[0]);
    expect($backoff[2])->toBeGreaterThan($backoff[1]);
    expect($backoff[3])->toBeGreaterThan($backoff[2]);
    expect($backoff[4])->toBeGreaterThan($backoff[3]);
});

test('falha no handle chama failed', function () {
    $logSpy = Log::spy();

    $job = new FailingTestJob([], 'payments');

    $job->failed(new RuntimeException('Falha intencional'));

    $logSpy->shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'Falha definitiva'));
});

test('logInfo registra mensagem com contexto', function () {
    $logSpy = Log::spy();

    $job = new TestJob([], 'payments');
    $job->exposeLogInfo('Mensagem de teste');

    $logSpy->shouldHaveReceived('info')
        ->once()
        ->withArgs(fn (string $message, array $context) => (
            str_contains($message, 'Mensagem de teste')
            && $context['job'] === TestJob::class
            && $context['domain'] === 'payments'
        ));
});

test('logWarning registra warning', function () {
    $logSpy = Log::spy();

    $job = new TestJob([], 'conta_certa');
    $job->exposeLogWarning('Aviso importante');

    $logSpy->shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => (
            str_contains($message, 'Aviso importante')
            && $context['domain'] === 'conta_certa'
        ));
});

test('logError registra error', function () {
    $logSpy = Log::spy();

    $job = new TestJob([], 'infractions');
    $job->exposeLogError('Erro crítico');

    $logSpy->shouldHaveReceived('error')
        ->once()
        ->withArgs(fn (string $message, array $context) => (
            str_contains($message, 'Erro crítico')
            && $context['domain'] === 'infractions'
        ));
});

test('pode ser dispatchado na fila transfeera', function () {
    TestJob::dispatch(['test' => true])->onQueue('transfeera');

    Queue::assertPushed(TestJob::class, function ($job) {
        return $job->queue === 'transfeera';
    });
});
