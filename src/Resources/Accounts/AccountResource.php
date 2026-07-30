<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Accounts;

use FlavioMoreir4\Transfeera\DTOs\Response\AccountResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de contas digitais (Hub de Contas).
 *
 * O Hub de Contas permite que uma única credencial gerencie múltiplas contas
 * digitais. Para operar em nome de uma conta específica, passe o accountId
 * nos métodos ou use o parâmetro ao chamar o Resource.
 *
 * Ao encerrar uma conta, todas as chaves Pix vinculadas são removidas.
 *
 * @example
 * ```php
 * // Criar nova conta digital
 * $account = Transfeera::accounts()->create([
 *     'name' => 'Empresa XYZ',
 *     'document' => '11222333444455',
 *     'email' => 'financeiro@xyz.com',
 * ]);
 *
 * // Listar contas
 * $accounts = Transfeera::accounts()->list();
 *
 * // Consultar conta específica
 * $account = Transfeera::accounts()->get('acc_123');
 *
 * // Encerrar conta e remover chaves Pix
 * Transfeera::accounts()->close('acc_123');
 * ```
 */
class AccountResource extends BaseResource
{
    private const string BASE_PATH = '/accounts';

    /**
     * Cria uma nova conta digital.
     *
     * @param  array{name: string, document: string, email: string, ...}  $data  Dados da conta
     */
    public function create(array $data): AccountResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_ACCOUNTS, self::BASE_PATH, $data, AccountResponseDTO::class);
    }

    /**
     * Lista as contas digitais vinculadas à credencial.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais
     * @return array<int, AccountResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_ACCOUNTS, self::BASE_PATH, $filters, AccountResponseDTO::class);
    }

    /**
     * Consulta uma conta digital pelo ID.
     *
     * @param  string  $id  ID da conta
     */
    public function get(string $id): AccountResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_ACCOUNTS, self::BASE_PATH.'/'.$id, [], AccountResponseDTO::class);
    }

    /**
     * Encerra (remove) uma conta digital.
     *
     * @param  string  $id  Identificador da conta
     */
    public function close(string $id): AccountResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_ACCOUNTS, self::BASE_PATH.'/'.$id.'/close', [], AccountResponseDTO::class);
    }
}
