<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera;

use FlavioMoreir4\Transfeera\Auth\TokenManager;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Http\MtlsConfigurator;
use FlavioMoreir4\Transfeera\Resources\Payments\BankResource;
use FlavioMoreir4\Transfeera\Resources\Payments\BatchResource;
use FlavioMoreir4\Transfeera\Resources\Payments\BilletResource;
use FlavioMoreir4\Transfeera\Resources\Payments\PixResource;
use FlavioMoreir4\Transfeera\Resources\Payments\RecurrenceResource;
use FlavioMoreir4\Transfeera\Resources\Payments\StatementResource;
use FlavioMoreir4\Transfeera\Resources\Payments\TransferResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\ChargeResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PaymentLinkResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixCashInResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixKeyResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixQrCodeResource;

/**
 * Ponto de entrada principal do SDK Laravel Transfeera.
 *
 * Expõe todos os Resources da API através de métodos fluentes.
 * Use via Facade {@see \FlavioMoreir4\Transfeera\Facades\Transfeera}
 * ou por injeção de dependência.
 *
 * @example
 * ```php
 * // Via Facade
 * Transfeera::batches()->create(['name' => 'Meu Lote']);
 *
 * // Via injeção
 * class MeuService {
 *     public function __construct(private TransfeeraClient $transfeera) {}
 * }
 * ```
 */
class TransfeeraClient
{
    private ?BatchResource $batches = null;
    private ?TransferResource $transfers = null;
    private ?BilletResource $billets = null;
    private ?BankResource $banks = null;
    private ?StatementResource $statement = null;
    private ?RecurrenceResource $recurrences = null;
    private ?PixResource $pix = null;
    private ?PixKeyResource $pixKeys = null;
    private ?PixQrCodeResource $pixQrCodes = null;
    private ?PixCashInResource $pixCashIn = null;
    private ?ChargeResource $charges = null;
    private ?PaymentLinkResource $paymentLinks = null;

    /**
     * @param  array<string, mixed>  $config  Configurações do pacote
     */
    public function __construct(
        private readonly array $config,
    ) {}

    /**
     * Cria o Connector compartilhado entre todos os Resources.
     */
    public function connector(): Connector
    {
        $environment = $this->config['environment'] ?? 'sandbox';
        $baseUrls = $this->config['base_urls'] ?? [];

        $resolvedBaseUrls = [
            Connector::DOMAIN_AUTH => $baseUrls['auth'][$environment] ?? '',
            Connector::DOMAIN_PAYMENTS => $baseUrls['payments'][$environment] ?? '',
            Connector::DOMAIN_CONTA_CERTA => $baseUrls['conta_certa'][$environment] ?? '',
        ];

        $tokenManager = new TokenManager(
            config: $this->config,
            authBaseUrl: $resolvedBaseUrls[Connector::DOMAIN_AUTH],
        );

        $mtls = new MtlsConfigurator(
            mtlsConfig: $this->config['mtls'] ?? [],
            environment: $environment,
        );

        return new Connector(
            tokenManager: $tokenManager,
            mtls: $mtls,
            config: $this->config,
            baseUrls: $resolvedBaseUrls,
        );
    }

    /**
     * Resource para gerenciamento de lotes de pagamentos.
     */
    public function batches(?string $accountId = null): BatchResource
    {
        return new BatchResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de transferências dentro de lotes.
     */
    public function transfers(?string $accountId = null): TransferResource
    {
        return new TransferResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de boletos.
     */
    public function billets(?string $accountId = null): BilletResource
    {
        return new BilletResource($this->connector(), $accountId);
    }

    /**
     * Resource para consulta de bancos.
     */
    public function banks(?string $accountId = null): BankResource
    {
        return new BankResource($this->connector(), $accountId);
    }

    /**
     * Resource para saldo e extrato.
     */
    public function statement(?string $accountId = null): StatementResource
    {
        return new StatementResource($this->connector(), $accountId);
    }

    /**
     * Resource para recorrências de pagamentos.
     */
    public function recurrences(?string $accountId = null): RecurrenceResource
    {
        return new RecurrenceResource($this->connector(), $accountId);
    }

    /**
     * Resource para operações Pix (consulta DICT e parse EMV).
     */
    public function pix(?string $accountId = null): PixResource
    {
        return new PixResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de Chaves Pix (Recebimentos).
     */
    public function pixKeys(?string $accountId = null): PixKeyResource
    {
        return new PixKeyResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de QR Codes Pix (Recebimentos).
     */
    public function pixQrCodes(?string $accountId = null): PixQrCodeResource
    {
        return new PixQrCodeResource($this->connector(), $accountId);
    }

    /**
     * Resource para consulta de Pix recebidos (Cash-in).
     */
    public function pixCashIn(?string $accountId = null): PixCashInResource
    {
        return new PixCashInResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de Cobranças (boleto + Pix).
     */
    public function charges(?string $accountId = null): ChargeResource
    {
        return new ChargeResource($this->connector(), $accountId);
    }

    /**
     * Resource para gerenciamento de Links de Pagamento.
     */
    public function paymentLinks(?string $accountId = null): PaymentLinkResource
    {
        return new PaymentLinkResource($this->connector(), $accountId);
    }

    /**
     * Retorna a configuração atual do pacote.
     *
     * @return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }
}
