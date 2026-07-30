<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Comando artisan para verificar a conectividade com a API da Transfeera.
 *
 * Testa se as credenciais estão configuradas, se o endpoint de autenticação
 * está acessível e se o mTLS está pronto (produção).
 *
 * @example
 * ```bash
 * php artisan transfeera:check
 * ```
 */
class CheckCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'transfeera:check';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Verifica a conectividade e configuração do Transfeera SDK';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $this->info('🔍 Verificando Transfeera SDK...');
        $this->newLine();

        $config = config('transfeera');

        $exitCode = self::SUCCESS;

        $exitCode = max($exitCode, $this->checkEnvironment($config));
        $exitCode = max($exitCode, $this->checkCredentials($config));
        $exitCode = max($exitCode, $this->checkMtls($config));
        $exitCode = max($exitCode, $this->checkAuthEndpoint($config));

        $this->newLine();

        if ($exitCode === self::SUCCESS) {
            $this->info('✅ Todas as verificações passaram.');
        } else {
            $this->warn('⚠️  Algumas verificações falharam. Revise as mensagens acima.');
        }

        return $exitCode;
    }

    /**
     * Verifica o ambiente configurado.
     */
    private function checkEnvironment(array $config): int
    {
        $env = $config['environment'] ?? 'sandbox';

        $this->line("📋 Ambiente: <comment>{$env}</comment>");

        if (! in_array($env, ['sandbox', 'production'], true)) {
            $this->error("❌ Ambiente inválido: '{$env}'. Use 'sandbox' ou 'production'.");

            return self::FAILURE;
        }

        $this->info('✅ Ambiente válido.');

        return self::SUCCESS;
    }

    /**
     * Verifica se as credenciais estão configuradas.
     */
    private function checkCredentials(array $config): int
    {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';

        if (empty($clientId)) {
            $this->warn('⚠️  TRANSFEERA_CLIENT_ID não configurado.');

            return self::FAILURE;
        }

        if (empty($clientSecret)) {
            $this->warn('⚠️  TRANSFEERA_CLIENT_SECRET não configurado.');

            return self::FAILURE;
        }

        $this->info('✅ Credenciais configuradas.');

        return self::SUCCESS;
    }

    /**
     * Verifica a configuração de mTLS.
     */
    private function checkMtls(array $config): int
    {
        $env = $config['environment'] ?? 'sandbox';

        if ($env !== 'production') {
            $this->info('ℹ️  mTLS: não verificado (sandbox).');

            return self::SUCCESS;
        }

        $certPath = $config['mtls']['cert_path'] ?? '';
        $keyPath = $config['mtls']['key_path'] ?? '';

        if (empty($certPath) || empty($keyPath)) {
            $this->warn('⚠️  mTLS: certificado ou chave não configurados.');

            return self::FAILURE;
        }

        $certOk = file_exists($certPath);
        $keyOk = file_exists($keyPath);

        if (! $certOk) {
            $this->warn("⚠️  Certificado mTLS não encontrado: {$certPath}");
        }

        if (! $keyOk) {
            $this->warn("⚠️  Chave mTLS não encontrada: {$keyPath}");
        }

        if ($certOk && $keyOk) {
            $this->info('✅ mTLS configurado e acessível.');

            return self::SUCCESS;
        }

        return self::FAILURE;
    }

    /**
     * Verifica se o endpoint de autenticação está acessível.
     */
    private function checkAuthEndpoint(array $config): int
    {
        $environment = $config['environment'] ?? 'sandbox';
        $urls = $config['base_urls'] ?? [];
        $authBaseUrl = $urls['auth'][$environment] ?? ($environment === 'production'
            ? 'https://login-api.transfeera.com'
            : 'https://login-api-sandbox.transfeera.com');

        $url = rtrim($authBaseUrl, '/').'/authorization';

        $this->line("🌐 Testando conexão: <comment>{$url}</comment>");

        try {
            $response = Http::timeout(10)->asForm()->post($url, [
                'grant_type' => 'client_credentials',
                'client_id' => $config['client_id'] ?? '',
                'client_secret' => $config['client_secret'] ?? '',
            ]);

            if ($response->successful()) {
                $this->info('✅ Endpoint de autenticação respondedor.');

                return self::SUCCESS;
            }

            if ($response->status() === 401) {
                // 401 com credenciais inválidas é esperado — o endpoint está vivo
                $this->info('✅ Endpoint de autenticação acessível (credenciais inválidas — configure corretamente).');

                return self::SUCCESS;
            }

            $this->warn("⚠️  Endpoint retornou HTTP {$response->status()}: {$response->body()}");

            return self::FAILURE;
        } catch (Exception $e) {
            $this->warn("⚠️  Não foi possível conectar ao endpoint: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
