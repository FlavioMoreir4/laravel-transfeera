<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Console\Commands;

use Illuminate\Console\Command;

/**
 * Comando artisan para instalar e validar a configuração do SDK Transfeera.
 *
 * Publica o arquivo de configuração e verifica se o ambiente
 * está configurado corretamente.
 *
 * @example
 * ```bash
 * php artisan transfeera:install
 * ```
 */
class InstallCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'transfeera:install';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Publica a configuração e valida o ambiente do Transfeera SDK';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $this->info('🔧 Instalando Laravel Transfeera SDK...');

        // Publicar configuração
        $this->call('vendor:publish', [
            '--tag' => 'transfeera-config',
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('✅ Configuração publicada em config/transfeera.php');

        // Validar ambiente
        $this->newLine();
        $this->validateEnvironment();

        $this->newLine();
        $this->info('✨ Instalação concluída!');
        $this->warn('Configure as variáveis de ambiente no .env:');
        $this->line('  TRANSFEERA_ENVIRONMENT=sandbox|production');
        $this->line('  TRANSFEERA_CLIENT_ID=seu_client_id');
        $this->line('  TRANSFEERA_CLIENT_SECRET=seu_client_secret');
        $this->line('  TRANSFEERA_MTLS_CERT_PATH=/caminho/cert.pem');
        $this->line('  TRANSFEERA_MTLS_KEY_PATH=/caminho/key.pem');

        return self::SUCCESS;
    }

    /**
     * Valida as configurações de ambiente.
     */
    private function validateEnvironment(): void
    {
        $environment = config('transfeera.environment', 'sandbox');

        $this->line("📋 Ambiente: <comment>{$environment}</comment>");

        if ($environment === 'production') {
            $certPath = config('transfeera.mtls.cert_path', '');
            $keyPath = config('transfeera.mtls.key_path', '');

            if (empty($certPath) || empty($keyPath)) {
                $this->warn('⚠️  Produção ativa, mas certificado mTLS não configurado!');
                $this->warn('   Configure TRANSFEERA_MTLS_CERT_PATH e TRANSFEERA_MTLS_KEY_PATH.');
            } elseif (! file_exists($certPath)) {
                $this->warn("⚠️  Certificado mTLS não encontrado em: {$certPath}");
            } elseif (! file_exists($keyPath)) {
                $this->warn("⚠️  Chave mTLS não encontrada em: {$keyPath}");
            } else {
                $this->info('✅ Certificado mTLS configurado e acessível.');
            }
        }

        $clientId = config('transfeera.client_id', '');
        $clientSecret = config('transfeera.client_secret', '');

        if (empty($clientId) || empty($clientSecret)) {
            $this->warn('⚠️  Client ID e/ou Client Secret não configurados.');
        } else {
            $this->info('✅ Credenciais de API configuradas.');
        }
    }
}
