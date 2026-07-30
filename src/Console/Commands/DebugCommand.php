<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Console\Commands;

use FlavioMoreir4\Transfeera\Http\Connector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Comando artisan para debug detalhado do Transfeera SDK.
 *
 * Exibe configuração, conectividade, status do cache e informações
 * do ambiente para diagnóstico rápido.
 *
 * @example
 * ```bash
 * php artisan transfeera:debug
 * php artisan transfeera:debug --verbose
 * ```
 */
class DebugCommand extends Command
{
    /**
     * O nome e a assinatura do comando.
     *
     * @var string
     */
    protected $signature = 'transfeera:debug
        {--verbose : Exibe payloads completos e detalhes adicionais}';

    /**
     * A descrição do comando.
     *
     * @var string
     */
    protected $description = 'Exibe diagnóstico completo do Transfeera SDK';

    /**
     * Executa o comando.
     */
    public function handle(): int
    {
        $this->info('🔬 Transfeera SDK — Diagnóstico Detalhado');
        $this->newLine();

        $this->displayEnvironment();
        $this->displayConfig();
        $this->displayBaseUrls();
        $this->displayEndpoints();
        $this->displayTokenStatus();

        $this->newLine();
        $this->info('✅ Diagnóstico concluído.');

        return self::SUCCESS;
    }

    /**
     * Exibe informações do ambiente PHP/Laravel.
     */
    private function displayEnvironment(): void
    {
        $this->line('📦 <options=bold>Ambiente</>');
        $this->line('  PHP:       <comment>'.PHP_VERSION.'</comment>');
        $this->line('  Laravel:   <comment>'.(app()->version() ?? 'N/A').'</comment>');
        $this->line('  Ambiente:  <comment>'.(app()->environment() ?? 'N/A').'</comment>');
        $this->line('  Debug:     <comment>'.(config('app.debug') ? 'true' : 'false').'</comment>');
        $this->newLine();
    }

    /**
     * Exibe a configuração do SDK (valores sensíveis omitidos).
     */
    private function displayConfig(): void
    {
        $config = config('transfeera');

        $this->line('⚙️ <options=bold>Configuração</>');

        $env = $config['environment'] ?? 'sandbox';
        $envOk = in_array($env, ['sandbox', 'production'], true);
        $this->line("  Environment:     <comment>{$env}</comment> ".($envOk ? '✅' : '⚠️'));

        $clientId = $config['client_id'] ?? '';
        if (! empty($clientId)) {
            $masked = substr((string) $clientId, 0, 4).str_repeat('*', max(0, strlen((string) $clientId) - 8)).substr((string) $clientId, -4);
            $this->line("  Client ID:       <comment>{$masked}</comment> ✅");
        } else {
            $this->line('  Client ID:       <comment>não configurado</comment> ⚠️');
        }

        $clientSecret = $config['client_secret'] ?? '';
        if (! empty($clientSecret)) {
            $this->line('  Client Secret:   <comment>****'.substr((string) $clientSecret, -4).'</comment> ✅');
        } else {
            $this->line('  Client Secret:   <comment>não configurado</comment> ⚠️');
        }

        $timeout = $config['timeout'] ?? 30;
        $this->line("  Timeout:         <comment>{$timeout}s</comment>");

        $retry = $config['retry']['max_attempts'] ?? 3;
        $retryDelay = $config['retry']['delay_ms'] ?? 100;
        $this->line("  Retry:           <comment>{$retry}x</comment> a cada <comment>{$retryDelay}ms</comment>");

        $cacheStore = $config['cache_store'] ?? 'default';
        $this->line("  Cache Store:     <comment>{$cacheStore}</comment>");

        $userAgent = $config['user_agent'] ?? 'Laravel Transfeera SDK';
        $this->line("  User-Agent:      <comment>{$userAgent}</comment>");

        $this->displayMtls($config);

        if ($this->option('verbose')) {
            $this->line('  Logging:         '.($config['logging']['enabled'] ?? false ? '✅ ativo' : '⬜ inativo'));
            $this->line('  Metrics:         '.($config['metrics']['enabled'] ?? false ? '✅ ativo' : '⬜ inativo'));
        }

        $this->newLine();
    }

    /**
     * Exibe status do mTLS.
     */
    private function displayMtls(array $config): void
    {
        $env = $config['environment'] ?? 'sandbox';

        if ($env !== 'production') {
            $this->line('  mTLS:            <comment>sandbox — não requerido</comment> ℹ️');

            return;
        }

        $certPath = $config['mtls']['cert_path'] ?? '';
        $keyPath = $config['mtls']['key_path'] ?? '';

        if (empty($certPath) || empty($keyPath)) {
            $this->line('  mTLS:            <comment>não configurado</comment> ⚠️');

            return;
        }

        $certOk = file_exists($certPath);
        $keyOk = file_exists($keyPath);

        $this->line('  mTLS Cert:        '.($certOk ? "✅ {$certPath}" : "❌ {$certPath}"));
        $this->line('  mTLS Key:         '.($keyOk ? "✅ {$keyPath}" : "❌ {$keyPath}"));
    }

    /**
     * Exibe as URLs base configuradas para cada domínio.
     */
    private function displayBaseUrls(): void
    {
        $this->line('🌐 <options=bold>URLs Base</>');

        $domains = [
            'Autenticação' => Connector::DOMAIN_AUTH,
            'Pagamentos' => Connector::DOMAIN_PAYMENTS,
            'Recebimentos' => Connector::DOMAIN_RECEIVABLES,
            'Pix Automático' => Connector::DOMAIN_PIX_AUTOMATICO,
            'Hub de Contas' => Connector::DOMAIN_ACCOUNTS,
            'MED/Infrações' => Connector::DOMAIN_INFRACTIONS,
            'Conta Certa' => Connector::DOMAIN_CONTA_CERTA,
        ];

        $env = config('transfeera.environment', 'sandbox');
        $urls = config('transfeera.base_urls', []);

        foreach ($domains as $label => $domain) {
            $url = $urls[$domain][$env] ?? $this->defaultUrl($domain, $env);
            $this->line("  {$label}:".str_repeat(' ', max(1, 17 - strlen($label)))."<comment>{$url}</comment>");
        }

        $this->newLine();
    }

    /**
     * Retorna a URL padrão para um domínio.
     */
    private function defaultUrl(string $domain, string $environment): string
    {
        return match ($domain) {
            Connector::DOMAIN_AUTH => $environment === 'production'
                ? 'https://login-api.transfeera.com'
                : 'https://login-api-sandbox.transfeera.com',
            Connector::DOMAIN_CONTA_CERTA => $environment === 'production'
                ? 'https://contacerta-api.mtls.transfeera.com'
                : 'https://contacerta-api-sandbox.transfeera.com',
            default => $environment === 'production'
                ? 'https://api.mtls.transfeera.com'
                : 'https://api-sandbox.transfeera.com',
        };
    }

    /**
     * Exibe os endpoints/Resources disponíveis.
     */
    private function displayEndpoints(): void
    {
        $this->line('📋 <options=bold>Resources Disponíveis</>');

        $resources = [
            'Pagamentos' => [
                'batches' => 'Lotes',
                'transfers' => 'Transferências',
                'billets' => 'Boletos',
                'banks' => 'Bancos',
                'statement' => 'Saldo/Extrato',
                'recurrences' => 'Recorrências',
                'pix' => 'Pix (consulta)',
            ],
            'Recebimentos' => [
                'pixKeys' => 'Chaves Pix',
                'pixQrCodes' => 'QR Codes Pix',
                'pixCashIn' => 'Pix Recebidos',
                'charges' => 'Cobranças',
                'paymentLinks' => 'Links de Pagamento',
            ],
            'Pix Automático' => [
                'pixAutomaticoAuthorizations' => 'Autorizações',
                'pixAutomaticoPaymentIntents' => 'Instruções de Pagamento',
            ],
            'Conta Certa' => [
                'contaCertaBanks' => 'Bancos',
                'contaCertaValidations' => 'Validações',
            ],
            'Hub de Contas' => [
                'accounts' => 'Contas Digitais',
            ],
            'MED/Infrações' => [
                'infractions' => 'Infrações',
            ],
            'Webhooks' => [
                'paymentsWebhooks' => 'Webhooks de Pagamentos',
                'receivablesWebhooks' => 'Webhooks de Recebimentos',
                'contaCertaWebhooks' => 'Webhooks de Conta Certa',
            ],
        ];

        foreach ($resources as $domain => $methods) {
            $this->line("  <options=bold>{$domain}</>");
            foreach ($methods as $method => $description) {
                $this->line("    {$method}: {$description}");
            }
        }

        $this->newLine();
    }

    /**
     * Exibe o status do token em cache.
     */
    private function displayTokenStatus(): void
    {
        $this->line('🔑 <options=bold>Token de Acesso</>');

        $cacheStore = config('transfeera.cache_store');
        $cache = Cache::store($cacheStore);

        $cached = $cache->get('transfeera_access_token');

        if ($cached === null) {
            $this->line('  Status:    <comment>não cacheado</comment>');
            $this->line('  (será obtido na primeira requisição)');
        } else {
            $expiresAt = $cached->expiresAt();
            $remaining = max(0, $expiresAt - time());
            $this->line('  Status:    <comment>cacheado</comment> ✅');
            $this->line("  Expira em: <comment>{$remaining}s</comment> (".date('H:i:s', $expiresAt).')');

            if ($this->option('verbose')) {
                $this->line('  Token:     <comment>'.substr((string) $cached->token(), 0, 20).'...</comment>');
            }
        }

        $this->newLine();
    }
}
