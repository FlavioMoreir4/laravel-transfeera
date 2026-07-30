<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Tests\Unit;

use FlavioMoreir4\Transfeera\Auth\AccessToken;
use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Exceptions\AccountException;
use FlavioMoreir4\Transfeera\Exceptions\ContaCertaException;
use FlavioMoreir4\Transfeera\Exceptions\InfractionException;
use FlavioMoreir4\Transfeera\Exceptions\PaymentException;
use FlavioMoreir4\Transfeera\Exceptions\PixAutomaticoException;
use FlavioMoreir4\Transfeera\Exceptions\ReceivableException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraAuthenticationException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraRateLimitException;
use FlavioMoreir4\Transfeera\Exceptions\TransfeeraValidationException;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->tokenManager = mock(TokenManager::class)
        ->shouldReceive('getToken')
        ->andReturn(new AccessToken('test-token', time() + 3600))
        ->getMock();

    $this->mtls = mock(MtlsConfigurator::class)
        ->shouldReceive('apply')
        ->andReturnArg(0)
        ->getMock();

    // Config com max_attempts=1 para testes de mapeamento de erro
    // (não queremos que o retry interfira na verificação de exceptions)
    $this->connector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 1, 'delay_ms' => 0],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
            Connector::DOMAIN_CONTA_CERTA => 'https://contacerta-api-sandbox.transfeera.com',
            'receivables' => 'https://api-sandbox.transfeera.com',
            'pix_automatico' => 'https://api-sandbox.transfeera.com',
            'accounts' => 'https://api-sandbox.transfeera.com',
            'infractions' => 'https://api-sandbox.transfeera.com',
        ],
    );

    // Connector com retry=3 para testar comportamento de retry
    $this->retryConnector = new Connector(
        tokenManager: $this->tokenManager,
        mtls: $this->mtls,
        config: [
            'user_agent' => 'Test',
            'timeout' => 30,
            'retry' => ['max_attempts' => 3, 'delay_ms' => 1],
        ],
        baseUrls: [
            Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com',
        ],
    );
});

// ─── Retry ────────────────────────────────────────────────────

test('retry recupera apos falha transiente e retorna sucesso', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['error' => 'Service Unavailable'], 503)
            ->push(['id' => 'batch_1', 'name' => 'Lote'], 200),
    ]);

    $result = $this->retryConnector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

test('retry esgota tentativas e retorna ultima resposta de erro', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['error' => 'Service Unavailable'], 503),
    ]);

    expect(fn () => $this->retryConnector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraException::class);
});

test('retry recupera apos erro 429 (rate limit)', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['message' => 'Too Many Requests'], 429)
            ->push(['id' => 'batch_1'], 200),
    ]);

    $result = $this->retryConnector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

test('retry recupera apos erro 422 (validacao)', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::sequence()
            ->push(['message' => 'Erro transiente'], 422)
            ->push(['id' => 'batch_1'], 200),
    ]);

    $result = $this->retryConnector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
});

// ─── Timeout / Falha de Rede ─────────────────────────────────
// NOTA: ConnectionException é propagada como Illuminate\Http\Client\ConnectionException
// pois o retry do Laravel não mapeia ConnectionException para nossas exceptions.
// Isso é um comportamento conhecido e pode ser tratado em versões futuras.

test('lanca ConnectionException em timeout de conexao', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => function () {
            throw new ConnectionException('cURL error 28: Connection timed out');
        },
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(ConnectionException::class);
});

test('lanca ConnectionException em falha de DNS', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => function () {
            throw new ConnectionException('cURL error 6: Could not resolve host');
        },
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(ConnectionException::class);
});

test('retry recupera apos timeout transiente', function () {
    $attempts = 0;
    Http::fake([
        'api-sandbox.transfeera.com/*' => function () use (&$attempts) {
            $attempts++;
            if ($attempts === 1) {
                throw new ConnectionException('cURL error 28: Timeout');
            }
            return Http::response(['id' => 'batch_1'], 200);
        },
    ]);

    $result = $this->retryConnector->get(Connector::DOMAIN_PAYMENTS, '/batches');

    expect($result['id'])->toBe('batch_1');
    expect($attempts)->toBe(2);
});

// ─── Mapeamento de Exceptions por Domínio ─────────────────────

test('mapeia erro payments para PaymentException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Saldo insuficiente'], 400),
    ]);

    expect(fn () => $this->connector->post(Connector::DOMAIN_PAYMENTS, '/batch', ['name' => 'X']))
        ->toThrow(PaymentException::class);
});

test('mapeia erro receivables para ReceivableException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Chave Pix inválida'], 400),
    ]);

    expect(fn () => $this->connector->get('receivables', '/pix/keys'))
        ->toThrow(ReceivableException::class);
});

test('mapeia erro pix_automatico para PixAutomaticoException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Limite excedido'], 400),
    ]);

    expect(fn () => $this->connector->post('pix_automatico', '/automatic-pix/authorizations', []))
        ->toThrow(PixAutomaticoException::class);
});

test('mapeia erro conta_certa para ContaCertaException', function () {
    Http::fake([
        'contacerta-api-sandbox.transfeera.com/*' => Http::response(['message' => 'Conta não encontrada'], 400),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_CONTA_CERTA, '/conta-certa/validations'))
        ->toThrow(ContaCertaException::class);
});

test('mapeia erro accounts para AccountException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'CNPJ duplicado'], 400),
    ]);

    expect(fn () => $this->connector->post('accounts', '/account', ['document' => '111']))
        ->toThrow(AccountException::class);
});

test('mapeia erro infractions para InfractionException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Infração não encontrada'], 404),
    ]);

    expect(fn () => $this->connector->get('infractions', '/med/infractions/abc'))
        ->toThrow(InfractionException::class);
});

test('mapeia erro 401 para TransfeeraAuthenticationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['error' => 'invalid_token'], 401),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraAuthenticationException::class);
});

test('mapeia erro 422 para TransfeeraValidationException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response([
            'message' => 'Validation failed',
            'errors' => ['name' => ['Campo obrigatório']],
        ], 422),
    ]);

    expect(fn () => $this->connector->post(Connector::DOMAIN_PAYMENTS, '/batches', ['name' => '']))
        ->toThrow(TransfeeraValidationException::class);
});

test('mapeia erro 429 para TransfeeraRateLimitException', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Too Many Requests'], 429),
    ]);

    expect(fn () => $this->connector->get(Connector::DOMAIN_PAYMENTS, '/batches'))
        ->toThrow(TransfeeraRateLimitException::class);
});

test('mapeia erro dominio desconhecido para TransfeeraException generica', function () {
    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['message' => 'Erro interno'], 500),
    ]);

    expect(fn () => $this->connector->get('unknown_domain', '/some/path'))
        ->toThrow(TransfeeraException::class);
});

// ─── Métodos HTTP ─────────────────────────────────────────────

test('envia DELETE corretamente', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/123' => Http::response([], 204),
    ]);

    $result = $this->connector->delete(Connector::DOMAIN_PAYMENTS, '/batch/123');

    expect($result)->toBe([]);
});

test('envia PUT corretamente', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/123' => Http::response(['id' => '123', 'name' => 'Editado'], 200),
    ]);

    $result = $this->connector->put(Connector::DOMAIN_PAYMENTS, '/batch/123', ['name' => 'Editado']);

    expect($result['name'])->toBe('Editado');
});

test('envia PATCH corretamente', function () {
    Http::fake([
        'api-sandbox.transfeera.com/batch/123' => Http::response(['id' => '123', 'name' => 'Parcial'], 200),
    ]);

    $result = $this->connector->patch(Connector::DOMAIN_PAYMENTS, '/batch/123', ['name' => 'Parcial']);

    expect($result['name'])->toBe('Parcial');
});

test('usa accountId no token quando informado', function () {
    $tokenManagerWithScope = mock(TokenManager::class)
        ->shouldReceive('getToken')
        ->with('acc_456')
        ->andReturn(new AccessToken('scoped-token', time() + 3600))
        ->getMock();

    $connectorWithScope = new Connector(
        tokenManager: $tokenManagerWithScope,
        mtls: $this->mtls,
        config: ['user_agent' => 'Test', 'timeout' => 30, 'retry' => ['max_attempts' => 1, 'delay_ms' => 0]],
        baseUrls: [Connector::DOMAIN_PAYMENTS => 'https://api-sandbox.transfeera.com'],
    );

    Http::fake([
        'api-sandbox.transfeera.com/*' => Http::response(['id' => 'batch_1'], 200),
    ]);

    $result = $connectorWithScope->get(Connector::DOMAIN_PAYMENTS, '/batches', [], 'acc_456');

    expect($result['id'])->toBe('batch_1');
});
