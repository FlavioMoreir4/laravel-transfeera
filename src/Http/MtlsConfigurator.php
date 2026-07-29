<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Http;

use FlavioMoreir4\Transfeera\Exceptions\TransfeeraException;
use Illuminate\Http\Client\PendingRequest;

/**
 * Aplica a configuração de certificado mTLS nas requisições HTTP.
 *
 * Em ambiente de produção, injeta o certificado e a chave privada
 * nas opções do cliente HTTP. Em sandbox, o mTLS não é exigido.
 */
class MtlsConfigurator
{
    /**
     * @param  array{cert_path: string, key_path: string}  $mtlsConfig
     * @param  string  $environment  'sandbox'|'production'
     */
    public function __construct(
        private readonly array $mtlsConfig,
        private readonly string $environment,
    ) {}

    /**
     * Aplica as opções de mTLS no request pendente, se necessário.
     *
     * @throws TransfeeraException
     */
    public function apply(PendingRequest $request): PendingRequest
    {
        if ($this->environment !== 'production') {
            return $request;
        }

        $certPath = $this->mtlsConfig['cert_path'] ?? '';
        $keyPath = $this->mtlsConfig['key_path'] ?? '';

        if (empty($certPath) || empty($keyPath)) {
            throw new TransfeeraException(
                message: 'mTLS é obrigatório em produção. Configure TRANSFEERA_MTLS_CERT_PATH e TRANSFEERA_MTLS_KEY_PATH no .env.',
                statusCode: 0,
            );
        }

        if (! file_exists($certPath)) {
            throw new TransfeeraException(
                message: "Certificado mTLS não encontrado no caminho: {$certPath}",
                statusCode: 0,
            );
        }

        if (! file_exists($keyPath)) {
            throw new TransfeeraException(
                message: "Chave mTLS não encontrada no caminho: {$keyPath}",
                statusCode: 0,
            );
        }

        return $request->withOptions([
            'cert' => $certPath,
            'ssl_key' => $keyPath,
        ]);
    }
}
