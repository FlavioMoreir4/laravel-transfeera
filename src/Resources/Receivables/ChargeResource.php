<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\DTOs\Response\ChargeResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Cobranças (boleto + Pix).
 *
 * Permite criar cobranças com pagamento via boleto bancário e/ou Pix,
 * consultar, cancelar e baixar comprovantes em PDF.
 *
 * @example
 * ```php
 * Transfeera::charges()->create([
 *     'payer_name' => 'João Silva',
 *     'value' => 5000, // R$ 50,00 em centavos
 * ]);
 * Transfeera::charges()->downloadPdf('charge_abc123');
 * ```
 */
class ChargeResource extends BaseResource
{
    private const string BASE_PATH = '/charges';

    /**
     * Cria uma nova cobrança (boleto e/ou Pix).
     *
     * @param  array<string, mixed>  $data  Dados da cobrança
     */
    public function create(array $data): ChargeResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $data, ChargeResponseDTO::class);
    }

    /**
     * Lista as cobranças cadastradas.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<int, ChargeResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $filters, ChargeResponseDTO::class);
    }

    /**
     * Consulta uma cobrança pelo ID.
     *
     * @param  string  $id  ID da cobrança
     */
    public function get(string $id): ChargeResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, [], ChargeResponseDTO::class);
    }

    /**
     * Cancela uma cobrança.
     *
     * @param  string  $id  ID da cobrança
     */
    public function cancel(string $id): ChargeResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id.'/cancel', [], ChargeResponseDTO::class);
    }

    /**
     * Faz o download do comprovante (PDF) de uma cobrança.
     *
     * Path oficial: GET /charges/{id}/receivables/{receivableId}/pdf
     *
     * @param  string  $id  ID da cobrança
     * @param  string  $receivableId  ID do recebível (boleto/pix gerado)
     * @return array<string, mixed>
     */
    public function downloadPdf(string $id, string $receivableId): array
    {
        return $this->connector->get(
            Connector::DOMAIN_RECEIVABLES,
            self::BASE_PATH.'/'.$id.'/receivables/'.$receivableId.'/pdf',
            accountId: $this->accountId,
        );
    }

    /**
     * Faz o download do comprovante (PDF) de uma cobrança avulsa.
     *
     * @deprecated Use downloadPdf($id, $receivableId) para path oficial.
     *
     * @param  string  $id  ID da cobrança
     * @return array<string, mixed>
     */
    public function downloadPdfByChargeId(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_RECEIVABLES,
            self::BASE_PATH.'/'.$id.'/pdf',
            accountId: $this->accountId,
        );
    }
}
