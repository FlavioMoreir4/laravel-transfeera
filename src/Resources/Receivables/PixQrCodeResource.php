<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de QR Codes Pix (Recebimentos).
 *
 * Suporta QR Codes estáticos, cobrança imediata (sem vencimento)
 * e cobrança com vencimento.
 *
 * @example
 * ```php
 * Transfeera::pixQrCodes()->createStatic(['key' => 'email@example.com']);
 * Transfeera::pixQrCodes()->createImmediate($chargeData);
 * Transfeera::pixQrCodes()->revoke('qrcode_abc123');
 * ```
 */
class PixQrCodeResource extends BaseResource
{
    private const BASE_PATH = '/v1/qrcodes';

    public function __construct(
        Connector $connector,
        ?string $accountId = null,
    ) {
        parent::__construct($connector, $accountId);
    }

    /**
     * Cria um QR Code estático (mesma chave, valor e dados fixos).
     *
     * @param  array<string, mixed>  $data  Dados do QR Code estático
     * @return array<string, mixed>
     */
    public function createStatic(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/static',
            $data,
            $this->accountId,
        );
    }

    /**
     * Cria uma cobrança Pix imediata (sem vencimento).
     *
     * @param  array<string, mixed>  $data  Dados da cobrança imediata
     * @return array<string, mixed>
     */
    public function createImmediate(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/immediate',
            $data,
            $this->accountId,
        );
    }

    /**
     * Cria uma cobrança Pix com vencimento.
     *
     * @param  array<string, mixed>  $data  Dados da cobrança com vencimento
     * @return array<string, mixed>
     */
    public function createDue(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/due',
            $data,
            $this->accountId,
        );
    }

    /**
     * Lista todos os QR Codes/cobranças Pix.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $filters,
            $this->accountId,
        );
    }

    /**
     * Consulta um QR Code/cobrança pelo ID.
     *
     * @param  string  $id  ID do QR Code
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id,
            accountId: $this->accountId,
        );
    }

    /**
     * Revoga uma cobrança Pix (imediata ou com vencimento).
     *
     * @param  string  $id  ID da cobrança a ser revogada
     * @return array<string, mixed>
     */
    public function revoke(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id . '/revoke',
            accountId: $this->accountId,
        );
    }
}
