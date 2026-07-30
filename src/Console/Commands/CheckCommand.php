<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Console\Commands;

use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Comando artisan para verificar conectividade e credenciais da API Transfeera.
 *
 * Testa se as credenciais estão configuradas, se o endpoint de autenticação
 * está acessível e se o mTLS está configurado em produção.
 *
 * @example
 * ```bash
 * php artisan transfeera:check
 * php artisan transfeera:check --silent
 * ```
 */
class CheckCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'transfeera:check {--silent : Apenas retorna o código de saída}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Verifica conectividade e credenciais da API Transfeera';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        if (! $this->option('silent')) {
            $this->info('🔍 Verificando conectividade e credenciais da API Transfeera...');
            $this->newLine();
        }

        $config = config('transfeera', []);

        $exitCode = self::SUCCESS;

        $exitCode = max($exitCode, $this->checkConfig($config));
        $exitCode = max($exitCode, $this->checkMtls($config));
        $exitCode = max($exitCode, $this->checkAuthEndpoint($config));

        if (! $this->option('silent')) {
            $this->newLine();

            if ($exitCode === self::SUCCESS) {
                $this->info('OK: Credenciais validadas');
            } else {
                $this->error('Falha na validação das credenciais');
            }
        }

        return $exitCode;
    }

    /**
     * Verifica se as configurações obrigatórias estão presentes.
     */
    private function checkConfig(array $config): int
    {
        $clientId = $config['client_id'] ?? '';
        $clientSecret = $config['client_secret'] ?? '';
        $environment = $config['environment'] ?? '';

        if (empty($clientId) || empty($clientSecret) || empty($environment)) {
            $this->lineError('Credenciais (client_id, client_secret, environment) não configuradas.');

            return self::FAILURE;
        }

        if (! in_array($environment, ['sandbox', 'production'], true)) {
            $this->lineError("Ambiente inválido: '{$environment}'. Use 'sandbox' ou 'production'.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Emite warning quando mTLS não está configurado em produção.
     */
    private function checkMtls(array $config): int
    {
        $environment = $config['environment'] ?? '';

        if ($environment !== 'production') {
            return self::SUCCESS;
        }

        $certPath = $config['mtls']['cert_path'] ?? '';
        $keyPath = $config['mtls']['key_path'] ?? '';

        if (empty($certPath) || empty($keyPath)) {
            $this->warn('Produção ativa, mas certificado/chave mTLS não configurados.');

            return self::SUCCESS;
        }

        if (! file_exists($certPath)) {
            $this->warn("Certificado mTLS não encontrado: {$certPath}");
        }

        if (! file_exists($keyPath)) {
            $this->warn("Chave mTLS não encontrada: {$keyPath}");
        }

        return self::SUCCESS;
    }

    /**
     * Verifica se as credenciais são válidas via requisição OAuth de token.
     */
    private function checkAuthEndpoint(array $config): int
    {
        $environment = $config['environment'] ?? 'sandbox';
        $urls = $config['base_urls'] ?? [];
        $authBaseUrl = $urls['auth'][$environment] ?? ($environment === 'production'
            ? 'https://login-api.transfeera.com'
            : 'https://login-api-sandbox.transfeera.com');

        $url = rtrim($authBaseUrl, '/').'/authorization';

        if (! $this->option('silent')) {
            $this->line("🌐 Testando autenticação: <comment>{$url}</comment>");
        }

        try {
            $response = Http::asForm()
                ->timeout(10)
                ->post($url, [
                    'grant_type' => 'client_credentials',
                    'client_id' => $config['client_id'] ?? '',
                    'client_secret' => $config['client_secret'] ?? '',
                ]);

            if ($response->successful()) {
                return self::SUCCESS;
            }

            $this->lineError('Falha na autenticação: '.$response->body());

            return self::FAILURE;
        } catch (TransfeeraException $e) {
            $this->lineError('Erro inesperado: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * Escreve uma linha de erro respeitando o modo silencioso.
     */
    private function lineError(string $message): void
    {
        if (! $this->option('silent')) {
            $this->error($message);
        }
    }
}
