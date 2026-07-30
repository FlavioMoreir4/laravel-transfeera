<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

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
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/payout_recurrences',
            $params,
            $this->accountId,
        );
    }

    /**
     * Lista os pagamentos gerados por uma recorrência.
     *
     * @param  string  $recurrenceId  Identificador da recorrência
     * @param  array<string, mixed>  $params  Filtros
     * @return array<string, mixed>
     */
    public function listPayments(string $recurrenceId, array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/payout_recurrences/{$recurrenceId}/payments",
            $params,
            $this->accountId,
        );
    }

    /**
     * Cancela uma recorrência.
     *
     * @param  string  $recurrenceId  Identificador da recorrência
     * @return array<string, mixed>
     */
    public function cancel(string $recurrenceId): array
    {
        return $this->connector->post(
            self::DOMAIN,
            "/payout_recurrences/{$recurrenceId}/cancel",
            [],
            $this->accountId,
        );
    }
}
