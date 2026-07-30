<?php

namespace FlavioMoreir4\Transfeera\Tests;

use FlavioMoreir4\Transfeera\Facades\Transfeera;
use FlavioMoreir4\Transfeera\TransfeeraServiceProvider;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

/**
 * Classe base para os testes do pacote.
 *
 * Usa Orchestra TestBench para simular uma aplicação Laravel
 * sem precisar de um projeto Laravel completo.
 */
abstract class TestCase extends OrchestraTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        // Limpa também o store 'array' usado pelo TokenManager
        Cache::store('array')->flush();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageProviders($app): array
    {
        return [
            TransfeeraServiceProvider::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Transfeera' => Transfeera::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('transfeera', [
            'environment' => 'sandbox',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'mtls' => [
                'cert_path' => '',
                'key_path' => '',
            ],
            'user_agent' => 'Transfeera SDK Test (test@example.com)',
            'cache_store' => 'array',
            'timeout' => 30,
            'retry' => [
                'max_attempts' => 1,
                'delay_ms' => 0,
            ],
            'enabled_apis' => [
                'payments' => true,
                'receivables' => true,
                'pix_automatico' => true,
                'conta_certa' => true,
                'accounts' => true,
                'infractions' => true,
            ],
            'base_urls' => [
                'auth' => [
                    'sandbox' => 'https://login-api-sandbox.transfeera.com',
                    'production' => 'https://login-api.transfeera.com',
                ],
                'payments' => [
                    'sandbox' => 'https://api-sandbox.transfeera.com',
                    'production' => 'https://api.mtls.transfeera.com',
                ],
                'conta_certa' => [
                    'sandbox' => 'https://contacerta-api-sandbox.transfeera.com',
                    'production' => 'https://contacerta-api.mtls.transfeera.com',
                ],
            ],
        ]);
    }
}
