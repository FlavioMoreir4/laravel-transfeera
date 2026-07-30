<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Console\Commands\CheckCommand;
use FlavioMoreir4\Transfeera\Console\Commands\InstallCommand;
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\Middleware\LoggingMiddleware;
use FlavioMoreir4\Transfeera\Http\Middleware\MetricsMiddleware;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use FlavioMoreir4\Transfeera\Listeners\LogTransfeeraWebhook;
use Illuminate\Support\ServiceProvider;
use Override;

/**
 * Service Provider do Laravel Transfeera SDK.
 *
 * Registra o cliente no container, publica a configuração
 * e registra os comandos artisan de instalação e verificação.
 */
class TransfeeraServiceProvider extends ServiceProvider
{
    /**
     * Eventos e listeners registrados automaticamente pelo pacote.
     *
     * @var array<class-string, array<class-string>>
     */
    protected $listen = [
        TransfeeraWebhookReceived::class => [
            LogTransfeeraWebhook::class,
        ],
    ];

    /**
     * Registra o binding do cliente no container.
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/transfeera.php',
            'transfeera',
        );

        $this->app->singleton(TokenManager::class, function ($app) {
            $config = $app['config']['transfeera'];
            $environment = $config['environment'] ?? 'sandbox';
            $urls = $config['base_urls'] ?? [];
            $authBaseUrl = $urls['auth'][$environment] ?? ($environment === 'production'
                ? 'https://login-api.transfeera.com'
                : 'https://login-api-sandbox.transfeera.com');

            return new TokenManager($config, $authBaseUrl);
        });

        $this->app->singleton(MtlsConfigurator::class, fn ($app) => new MtlsConfigurator(
            mtlsConfig: $app['config']['transfeera']['mtls'] ?? [],
            environment: $app['config']['transfeera']['environment'] ?? 'sandbox',
        ));

        // Middlewares opcionais
        $this->app->singleton(LoggingMiddleware::class, fn ($app) => new LoggingMiddleware(
            enabled: $app['config']['transfeera']['logging']['enabled'] ?? false,
            level: $app['config']['transfeera']['logging']['level'] ?? 'info',
            logHeaders: $app['config']['transfeera']['logging']['headers'] ?? false,
        ));

        $this->app->singleton(MetricsMiddleware::class, fn ($app) => new MetricsMiddleware(
            enabled: $app['config']['transfeera']['metrics']['enabled'] ?? false,
            prefix: $app['config']['transfeera']['metrics']['prefix'] ?? 'transfeera',
        ));

        $this->app->singleton(Connector::class, fn ($app) => new Connector(
            tokenManager: $app->make(TokenManager::class),
            mtls: $app->make(MtlsConfigurator::class),
            config: $app['config']['transfeera'],
            baseUrls: $this->resolveBaseUrls($app['config']['transfeera']),
            loggingMiddleware: $app->make(LoggingMiddleware::class),
            metricsMiddleware: $app->make(MetricsMiddleware::class),
        ));

        $this->app->singleton('transfeera', fn ($app) => new TransfeeraClient(
            config: $app['config']['transfeera'],
            loggingMiddleware: $app->make(LoggingMiddleware::class),
            metricsMiddleware: $app->make(MetricsMiddleware::class),
        ));

        $this->app->alias('transfeera', TransfeeraClient::class);
    }

    /**
     * Inicializa o pacote.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/webhooks.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/transfeera.php' => $this->app->configPath('transfeera.php'),
            ], 'transfeera-config');

            $this->publishes([
                __DIR__.'/../routes/webhooks.php' => $this->app->basePath('routes/transfeera-webhooks.php'),
            ], 'transfeera-routes');

            $this->commands([
                InstallCommand::class,
                CheckCommand::class,
            ]);
        }

        $this->validateConfig();
    }

    /**
     * Valida a configuração do pacote e emite warnings se necessário.
     */
    private function validateConfig(): void
    {
        /** @var array<string, mixed> $config */
        $config = $this->app->make('config')->get('transfeera', []);

        $this->validateEnvironment($config);
        $this->validateCredentials($config);
        $this->validateMtls($config);
    }

    /**
     * Valida o ambiente configurado.
     */
    private function validateEnvironment(array $config): void
    {
        $env = $config['environment'] ?? '';

        if (! in_array($env, ['sandbox', 'production'], true)) {
            logger()->warning('Transfeera SDK: ambiente inválido configurado. Use "sandbox" ou "production".');
        }
    }

    /**
     * Valida se as credenciais estão presentes.
     */
    private function validateCredentials(array $config): void
    {
        if (empty($config['client_id']) || empty($config['client_secret'])) {
            logger()->warning('Transfeera SDK: client_id e/ou client_secret não configurados.');
        }
    }

    /**
     * Valida a configuração de mTLS em produção.
     */
    private function validateMtls(array $config): void
    {
        $env = $config['environment'] ?? '';

        if ($env !== 'production') {
            return;
        }

        $certPath = $config['mtls']['cert_path'] ?? '';
        $keyPath = $config['mtls']['key_path'] ?? '';

        if (empty($certPath) || empty($keyPath)) {
            logger()->warning('Transfeera SDK: produção ativa, mas mTLS não configurado.');

            return;
        }

        if (! file_exists($certPath)) {
            logger()->warning("Transfeera SDK: certificado mTLS não encontrado em: {$certPath}");
        }

        if (! file_exists($keyPath)) {
            logger()->warning("Transfeera SDK: chave mTLS não encontrada em: {$keyPath}");
        }
    }

    /**
     * Resolve as URLs base por domínio conforme ambiente.
     *
     * @return array<string, string>
     */
    private function resolveBaseUrls(array $config): array
    {
        $environment = $config['environment'] ?? 'sandbox';
        $urls = $config['base_urls'] ?? [];

        return [
            Connector::DOMAIN_AUTH => $urls['auth'][$environment] ?? ($environment === 'production'
                ? 'https://login-api.transfeera.com'
                : 'https://login-api-sandbox.transfeera.com'),
            Connector::DOMAIN_PAYMENTS => $urls['payments'][$environment] ?? ($environment === 'production'
                ? 'https://api.transfeera.com'
                : 'https://api-sandbox.transfeera.com'),
            Connector::DOMAIN_CONTA_CERTA => $urls['conta_certa'][$environment] ?? ($environment === 'production'
                ? 'https://api.transfeera.com'
                : 'https://api-sandbox.transfeera.com'),
        ];
    }
}
