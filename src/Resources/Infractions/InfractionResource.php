<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Infractions;

use FlavioMoreir4\Transfeera\DTOs\Response\InfractionAnalysisResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\InfractionResponseDTO;
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
    private const string BASE_PATH = '/med/infractions';

    /**
     * Lista as infrações cadastradas.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<int, InfractionResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_INFRACTIONS, self::BASE_PATH, $filters, InfractionResponseDTO::class);
    }

    /**
     * Consulta uma infração pelo ID.
     *
     * @param  string  $id  ID da infração
     */
    public function get(string $id): InfractionResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_INFRACTIONS, self::BASE_PATH.'/'.$id, [], InfractionResponseDTO::class);
    }

    /**
     * Envia uma análise individual para contestação/devolução.
     *
     * A infraction_id vai na URL: POST /med/infractions/{id}/analysis
     *
     * @param  string  $id  ID da infração
     * @param  array{type: string, refund_amount?: int, description?: string}  $data  Dados da análise
     */
    public function submitAnalysis(string $id, array $data): InfractionAnalysisResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_INFRACTIONS, self::BASE_PATH.'/'.$id.'/analysis', $data, InfractionAnalysisResponseDTO::class);
    }

    /**
     * Envia análises em lote para múltiplas infrações.
     *
     * @param  array<int, array{infraction_id: string, type: string, refund_amount?: int, description?: string}>  $analyses  Lista de análises
     * @return array<int, InfractionAnalysisResponseDTO>
     */
    public function submitBatchAnalysis(array $analyses): array
    {
        $response = $this->connector->post(
            Connector::DOMAIN_INFRACTIONS,
            self::BASE_PATH.'/analysis',
            $analyses,
            $this->accountId,
        );

        return $this->toDTOList(InfractionAnalysisResponseDTO::class, $this->extractDataList($response));
    }
}
