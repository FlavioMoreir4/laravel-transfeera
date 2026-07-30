<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
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
 * e registra o comando artisan de instalação.
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
            ]);
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
