<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixQrCodeResponseDTO;
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
    private const string BASE_PATH = '/pix/qrcode';

    /**
     * Cria um QR Code estático (mesma chave, valor e dados fixos).
     *
     * @param  array<string, mixed>  $data  Dados do QR Code estático
     */
    public function createStatic(array $data): PixQrCodeResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/static', $data, PixQrCodeResponseDTO::class);
    }

    /**
     * Cria uma cobrança Pix imediata (sem vencimento).
     *
     * @param  array<string, mixed>  $data  Dados da cobrança imediata
     */
    public function createImmediate(array $data): PixQrCodeResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/collection/immediate', $data, PixQrCodeResponseDTO::class);
    }

    /**
     * Cria uma cobrança Pix com vencimento.
     *
     * @param  array<string, mixed>  $data  Dados da cobrança com vencimento
     */
    public function createDue(array $data): PixQrCodeResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/collection/dueDate', $data, PixQrCodeResponseDTO::class);
    }

    /**
     * Lista todos os QR Codes/cobranças Pix.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<int, PixQrCodeResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $filters, PixQrCodeResponseDTO::class);
    }

    /**
     * Consulta um QR Code/cobrança pelo ID.
     *
     * @param  string  $id  ID do QR Code
     */
    public function get(string $id): PixQrCodeResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, [], PixQrCodeResponseDTO::class);
    }

    /**
     * Revoga uma cobrança Pix (imediata ou com vencimento).
     *
     * @param  string  $id  ID da cobrança a ser revogada
     */
    public function revoke(string $id): OperationResponseDTO
    {
        return $this->deleteDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, OperationResponseDTO::class);
    }
}
