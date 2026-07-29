<?php

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
        'key_path'  => env('TRANSFEERA_MTLS_KEY_PATH', ''),
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
    'cache_store' => env('TRANSFEERA_CACHE_STORE', null),

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
        'delay_ms'     => env('TRANSFEERA_RETRY_DELAY', 100),
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
        'payments'     => env('TRANSFEERA_ENABLE_PAYMENTS', true),
        'receivables'  => env('TRANSFEERA_ENABLE_RECEIVABLES', true),
        'pix_automatico' => env('TRANSFEERA_ENABLE_PIX_AUTOMATICO', true),
        'conta_certa'  => env('TRANSFEERA_ENABLE_CONTA_CERTA', true),
        'accounts'     => env('TRANSFEERA_ENABLE_ACCOUNTS', true),
        'infractions'  => env('TRANSFEERA_ENABLE_INFRACTIONS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs base por ambiente e sub-API
    |--------------------------------------------------------------------------
    |
    | Não altere a menos que a Transfeera mude seus endpoints.
    |
    */
    'base_urls' => [
        'auth' => [
            'sandbox'    => 'https://login-api-sandbox.transfeera.com',
            'production' => 'https://login-api.transfeera.com',
        ],
        'payments' => [
            'sandbox'    => 'https://api-sandbox.transfeera.com',
            'production' => 'https://api.mtls.transfeera.com',
        ],
        'conta_certa' => [
            'sandbox'    => 'https://contacerta-api-sandbox.transfeera.com',
            'production' => 'https://contacerta-api.mtls.transfeera.com',
        ],
    ],
];
