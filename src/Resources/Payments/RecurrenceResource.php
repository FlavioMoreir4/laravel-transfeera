<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\RecurrencePaymentResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\RecurrenceResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de recorrências de pagamentos.
 *
 * @see https://docs.transfeera.dev/reference/recurrences
 */
class RecurrenceResource extends BaseResource
{
    private const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Lista todas as recorrências cadastradas.
     *
     * @param  array<string, mixed>  $params  Filtros (page, per_page, status)
     * @return array<int, RecurrenceResponseDTO>
     */
    public function list(array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, '/payout_recurrences', $params, RecurrenceResponseDTO::class);
    }

    /**
     * Lista os pagamentos gerados por uma recorrência.
     *
     * @param  string  $recurrenceId  Identificador da recorrência
     * @param  array<string, mixed>  $params  Filtros
     * @return array<int, RecurrencePaymentResponseDTO>
     */
    public function listPayments(string $recurrenceId, array $params = []): array
    {
        return $this->getDTOList(self::DOMAIN, "/payout_recurrences/{$recurrenceId}/payments", $params, RecurrencePaymentResponseDTO::class);
    }

    /**
     * Cancela uma recorrência.
     *
     * @param  string  $recurrenceId  Identificador da recorrência
     */
    public function cancel(string $recurrenceId): OperationResponseDTO
    {
        return $this->postDTO(self::DOMAIN, "/payout_recurrences/{$recurrenceId}/cancel", [], OperationResponseDTO::class);
    }
}
