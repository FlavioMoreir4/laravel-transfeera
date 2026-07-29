<?php

declare(strict_types=1);

use FlavioMoreir4\Transfeera\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Transfeera Webhook Routes
|--------------------------------------------------------------------------
|
| Essas rotas são carregadas automaticamente pelo ServiceProvider.
| Você pode publicá-las com:
|   php artisan vendor:publish --tag=transfeera-routes
|
| Configure os secrets em config/transfeera.php:
|   'webhook_secret' => 'secret-geral',
|   'webhook_secrets' => [
|       'payments' => 'secret-pagamentos',
|       'receivables' => 'secret-recebimentos',
|       'conta_certa' => 'secret-conta-certa',
|   ],
|
*/

Route::post('webhooks/transfeera/payments', [WebhookController::class, 'payments'])
    ->name('transfeera.webhooks.payments');

Route::post('webhooks/transfeera/receivables', [WebhookController::class, 'receivables'])
    ->name('transfeera.webhooks.receivables');

Route::post('webhooks/transfeera/conta-certa', [WebhookController::class, 'contaCerta'])
    ->name('transfeera.webhooks.conta_certa');
