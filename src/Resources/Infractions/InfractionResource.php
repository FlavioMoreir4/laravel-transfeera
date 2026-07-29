<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Infractions;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de infrações MED (Mecanismo Especial de Devolução).
 *
 * O MED permite gerenciar notificações de infrações relacionadas a transações Pix,
 * incluindo consulta de infrações e envio de análises para contestação.
 *
 * Valores monetários (amount, refund_amount) são sempre em **centavos**.
 *
 * @example
 * ```php
 * // Listar infrações
 * $infractions = Transfeera::infractions()->list();
 *
 * // Consultar infração por ID
 * $infraction = Transfeera::infractions()->get('inf_123');
 *
 * // Enviar análise individual
 * Transfeera::infractions()->submitAnalysis([
 *     'infraction_id' => 'inf_123',
 *     'type' => 'refund',
 *     'refund_amount' => 5000,
 *     'description' => 'Devolução por acordo entre as partes',
 * ]);
 *
 * // Enviar análise em lote
 * Transfeera::infractions()->submitBatchAnalysis([
 *     ['infraction_id' => 'inf_001', 'type' => 'refund', 'refund_amount' => 3000],
 *     ['infraction_id' => 'inf_002', 'type' => 'contest', 'description' => 'Pagamento correto'],
 * ]);
 * ```
 */
class InfractionResource extends BaseResource
{
    private const BASE_PATH = '/v1/infractions';

    public function __construct(
        Connector $connector,
        ?string $accountId = null,
    ) {
        parent::__construct($connector, $accountId);
    }

    /**
     * Lista as infrações cadastradas.
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
     * Consulta uma infração pelo ID.
     *
     * @param  string  $id  ID da infração
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
     * Envia uma análise individual para contestação/devolução.
     *
     * @param  array{infraction_id: string, type: string, refund_amount?: int, description?: string}  $data  Dados da análise
     * @return array<string, mixed>
     */
    public function submitAnalysis(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/analysis',
            $data,
            $this->accountId,
        );
    }

    /**
     * Envia análises em lote para múltiplas infrações.
     *
     * @param  array<int, array{infraction_id: string, type: string, refund_amount?: int, description?: string}>  $analyses  Lista de análises
     * @return array<string, mixed>
     */
    public function submitBatchAnalysis(array $analyses): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/batch-analysis',
            $analyses,
            $this->accountId,
        );
    }
}
