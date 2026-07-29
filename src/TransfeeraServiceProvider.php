<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera;

use Override;
use FlavioMoreir4\Transfeera\Console\Commands\InstallCommand;
use FlavioMoreir4\Transfeera\Events\TransfeeraWebhookReceived;
use FlavioMoreir4\Transfeera\Listeners\LogTransfeeraWebhook;
use Illuminate\Support\ServiceProvider;

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
            __DIR__ . '/../config/transfeera.php',
            'transfeera',
        );

        $this->app->singleton('transfeera', fn($app) => new TransfeeraClient(
            config: $app['config']['transfeera'],
        ));

        $this->app->alias('transfeera', TransfeeraClient::class);
    }

    /**
     * Inicializa o pacote.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/webhooks.php');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/transfeera.php' => $this->app->configPath('transfeera.php'),
            ], 'transfeera-config');

            $this->publishes([
                __DIR__ . '/../routes/webhooks.php' => $this->app->basePath('routes/transfeera-webhooks.php'),
            ], 'transfeera-routes');

            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
