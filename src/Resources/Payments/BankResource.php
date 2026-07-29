<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para consulta de bancos disponíveis.
 *
 * @see https://docs.transfeera.dev/reference/banks
 */
class BankResource extends BaseResource
{
    private const DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Retorna a lista de bancos disponíveis para pagamentos.
     *
     * @param  array<string, mixed>  $params  Filtros (código, nome, etc.)
     * @return array<string, mixed>
     */
    public function list(array $params = []): array
    {
        return $this->connector->get(
            self::DOMAIN,
            '/bank',
            $params,
            $this->accountId,
        );
    }
}
