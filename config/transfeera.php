<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Ambiente
    |--------------------------------------------------------------------------
    |
    | Define o ambiente de execução: 'sandbox' para testes ou 'production'.
    | Em produção, o mTLS é obrigatório para as APIs que exigem certificado.
    |
    */
    'environment' => env('TRANSFEERA_ENVIRONMENT', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Credenciais de autenticação (client_credentials)
    |--------------------------------------------------------------------------
    |
    | Obtidas no painel da Transfeera. O client_id e client_secret são
    | usados para gerar o token de acesso via OAuth 2.0.
    |
    */
    'client_id' => env('TRANSFEERA_CLIENT_ID', ''),
    'client_secret' => env('TRANSFEERA_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Certificado mTLS (obrigatório em produção)
    |--------------------------------------------------------------------------
    |
    | Caminhos absolutos para o certificado e chave privada (.pem) exigidos
    | pelas APIs de Pagamentos e Conta Certa em produção.
    |
    */
    'mtls' => [
        'cert_path' => env('TRANSFEERA_MTLS_CERT_PATH', ''),
        'key_path' => env('TRANSFEERA_MTLS_KEY_PATH', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | User-Agent
    |--------------------------------------------------------------------------
    |
    | Identificação da aplicação cliente, conforme exigido pela Transfeera.
    | Formato recomendado: "NomeApp (email@dominio.com)"
    |
    */
    'user_agent' => env('TRANSFEERA_USER_AGENT', 'Laravel App (contato@exemplo.com)'),

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Store do Laravel Cache usada para armazenar o token de acesso.
    | Padrão: 'file' (usa o cache padrão da aplicação).
    |
    */
    'cache_store' => env('TRANSFEERA_CACHE_STORE'),

    /*
    |--------------------------------------------------------------------------
    | Timeout e Retry
    |--------------------------------------------------------------------------
    |
    | Configurações de timeout (segundos) e tentativas de retry para
    | requisições HTTP à API.
    |
    */
    'timeout' => env('TRANSFEERA_TIMEOUT', 30),
    'retry' => [
        'max_attempts' => env('TRANSFEERA_RETRY_MAX', 3),
        'delay_ms' => env('TRANSFEERA_RETRY_DELAY', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sub-APIs ativas
    |--------------------------------------------------------------------------
    |
    | Permite desabilitar sub-APIs que o projeto não utiliza, evitando
    | configuração desnecessária.
    |
    */
    'enabled_apis' => [
        'payments' => env('TRANSFEERA_ENABLE_PAYMENTS', true),
        'receivables' => env('TRANSFEERA_ENABLE_RECEIVABLES', true),
        'pix_automatico' => env('TRANSFEERA_ENABLE_PIX_AUTOMATICO', true),
        'conta_certa' => env('TRANSFEERA_ENABLE_CONTA_CERTA', true),
        'accounts' => env('TRANSFEERA_ENABLE_ACCOUNTS', true),
        'infractions' => env('TRANSFEERA_ENABLE_INFRACTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Secret
    |--------------------------------------------------------------------------
    |
    | Segredo usado para validar a assinatura HMAC-SHA256 dos webhooks.
    | Você pode usar um único secret global ou definir secrets por domínio.
    |
    */
    'webhook_secret' => env('TRANSFEERA_WEBHOOK_SECRET'),

    'webhook_secrets' => [
        'payments' => env('TRANSFEERA_WEBHOOK_SECRET_PAYMENTS'),
        'receivables' => env('TRANSFEERA_WEBHOOK_SECRET_RECEIVABLES'),
        'conta_certa' => env('TRANSFEERA_WEBHOOK_SECRET_CONTA_CERTA'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging (Middleware de Log)
    |--------------------------------------------------------------------------
    |
    | Configuração do LoggingMiddleware que registra todas as requisições
    | à API Transfeera com níveis, sanitização e truncamento.
    |
    */
    'logging' => [
        'enabled' => env('TRANSFEERA_LOGGING_ENABLED', true),
        'channel' => env('TRANSFEERA_LOGGING_CHANNEL', 'stack'),
        'level' => env('TRANSFEERA_LOGGING_LEVEL', 'info'),
        'log_headers' => env('TRANSFEERA_LOGGING_LOG_HEADERS', false),
        'log_response_body' => env('TRANSFEERA_LOGGING_LOG_RESPONSE_BODY', false),
        'sanitize' => env('TRANSFEERA_LOGGING_SANITIZE', true),
        'max_body_length' => env('TRANSFEERA_LOGGING_MAX_BODY_LENGTH', 4096),
        'level_by_domain' => [
            // 'payments' => 'debug',
            // 'receivables' => 'info',
        ],
        'level_by_status' => [
            // 500 => 'error',
            // 429 => 'warning',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Métricas (Middleware de Métricas)
    |--------------------------------------------------------------------------
    |
    | Configuração do MetricsMiddleware que coleta métricas das requisições.
    | O placeholder pode ser substituído por implementação Prometheus/StatsD.
    |
    */
    'metrics' => [
        'enabled' => env('TRANSFEERA_METRICS_ENABLED', false),
        'prefix' => env('TRANSFEERA_METRICS_PREFIX', 'transfeera'),
    ],

];
