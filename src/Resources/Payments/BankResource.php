<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\BankResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para consulta de bancos disponíveis para pagamentos.
 *
 * @see https://docs.transfeera.dev/reference/get_bank.md
 */
class BankResource extends BaseResource
{
    protected const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Retorna a lista de bancos disponíveis.
     *
     * @return array<int, BankResponseDTO>
     */
    public function list(): array
    {
        return $this->getDTOList(self::DOMAIN, '/bank', [], BankResponseDTO::class);
    }
}
