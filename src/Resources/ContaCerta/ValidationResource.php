<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\ContaCerta;

use FlavioMoreir4\Transfeera\DTOs\Response\ValidationResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para validação de contas bancárias (Conta Certa).
 *
 * A Conta Certa permite validar dados bancários (titular, CPF/CNPJ, agência, conta)
 * antes de realizar pagamentos, reduzindo o risco de devolução por dados incorretos.
 *
 * @example
 * ```php
 * $validation = Transfeera::contaCertaValidations()->create([
 *     'bank_code' => '341',
 *     'agency' => '1234',
 *     'account' => '56789',
 *     'document' => '12345678909',
 *     'account_type' => 'checking',
 * ]);
 * Transfeera::contaCertaValidations()->list();
 * Transfeera::contaCertaValidations()->get('val_123');
 * ```
 */
class ValidationResource extends BaseResource
{
    private const string BASE_PATH = '/validation';

    /**
     * Cria uma nova validação de conta bancária.
     *
     * @param  array<string, mixed>  $data  Dados da conta a ser validada
     */
    public function create(array $data): ValidationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_CONTA_CERTA, self::BASE_PATH, $data, ValidationResponseDTO::class);
    }

    /**
     * Lista as validações realizadas.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais (status, data, paginação)
     * @return array<int, ValidationResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_CONTA_CERTA, self::BASE_PATH, $filters, ValidationResponseDTO::class);
    }

    /**
     * Consulta uma validação pelo ID.
     *
     * @param  string  $id  ID da validação
     */
    public function get(string $id): ValidationResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_CONTA_CERTA, self::BASE_PATH.'/'.$id, [], ValidationResponseDTO::class);
    }
}
