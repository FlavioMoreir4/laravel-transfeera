<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera;

use FlavioMoreir4\Transfeera\Console\Commands\InstallCommand;
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
     * Registra o binding do cliente no container.
     */
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
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/transfeera.php' => $this->app->configPath('transfeera.php'),
            ], 'transfeera-config');

            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
