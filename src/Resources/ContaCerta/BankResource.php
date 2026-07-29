<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\ContaCerta;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para consulta de bancos no domínio Conta Certa.
 *
 * Diferente do {@see \FlavioMoreir4\Transfeera\Resources\Payments\BankResource}
 * que retorna bancos para pagamentos, este Resource retorna a lista de bancos
 * suportados pela validação Conta Certa.
 *
 * @example
 * ```php
 * $banks = Transfeera::contaCertaBanks()->list();
 * ```
 */
class BankResource extends BaseResource
{
    private const string BASE_PATH = '/bank';

    /**
     * Lista os bancos suportados pela Conta Certa.
     *
     * @return array<string, mixed>
     */
    public function list(): array
    {
        return $this->connector->get(
            Connector::DOMAIN_CONTA_CERTA,
            self::BASE_PATH,
            accountId: $this->accountId,
        );
    }
}
