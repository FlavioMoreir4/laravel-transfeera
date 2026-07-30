<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

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
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $data,
            $this->accountId,
        );
    }

    /**
     * Lista as cobranças cadastradas.
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
     * Consulta uma cobrança pelo ID.
     *
     * @param  string  $id  ID da cobrança
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id,
            accountId: $this->accountId,
        );
    }

    /**
     * Cancela uma cobrança.
     *
     * @param  string  $id  ID da cobrança
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/cancel',
            accountId: $this->accountId,
        );
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
            Connector::DOMAIN_PAYMENTS,
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
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/pdf',
            accountId: $this->accountId,
        );
    }
}
