<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

use FlavioMoreir4\Transfeera\DTOs\Response\OperationResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\PixKeyResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Chaves Pix (Recebimentos).
 *
 * Gerencia o ciclo de vida de chaves Pix: cadastro, verificação,
 * portabilidade e exclusão.
 *
 * @example
 * ```php
 * Transfeera::pixKeys()->list();
 * Transfeera::pixKeys()->create(['type' => 'cpf', 'value' => '12345678909']);
 * Transfeera::pixKeys()->verify('key_abc123', '123456');
 * ```
 */
class PixKeyResource extends BaseResource
{
    private const string BASE_PATH = '/pix/key';

    /**
     * Lista todas as chaves Pix cadastradas.
     *
     * @return array<int, PixKeyResponseDTO>
     */
    public function list(): array
    {
        return $this->getDTOList(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, [], PixKeyResponseDTO::class);
    }

    /**
     * Consulta uma chave Pix pelo ID.
     *
     * @param  string  $id  ID da chave Pix
     */
    public function get(string $id): PixKeyResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, [], PixKeyResponseDTO::class);
    }

    /**
     * Cria uma nova chave Pix.
     *
     * @param  array{type: string, value: string}  $data  Dados da chave (type: cpf, cnpj, email, phone, evp)
     */
    public function create(array $data): PixKeyResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH, $data, PixKeyResponseDTO::class);
    }

    /**
     * Remove uma chave Pix.
     *
     * @param  string  $id  ID da chave Pix
     */
    public function delete(string $id): OperationResponseDTO
    {
        return $this->deleteDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id, OperationResponseDTO::class);
    }

    /**
     * Reenvia o código de verificação para a chave Pix.
     *
     * @param  string  $id  ID da chave Pix
     */
    public function resendVerificationCode(string $id): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id.'/resendVerificationCode', [], OperationResponseDTO::class);
    }

    /**
     * Verifica uma chave Pix com o código recebido.
     *
     * @param  string  $id  ID da chave Pix
     * @param  string  $code  Código de verificação
     */
    public function verify(string $id, string $code): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id.'/verify', ['code' => $code], OperationResponseDTO::class);
    }

    /**
     * Inicia o processo de portabilidade/reivindicação de chave Pix.
     *
     * A chave é passada na URL: POST /pix/key/{key}/claim
     *
     * @param  string  $key  Chave Pix a ser reivindicada
     */
    public function claim(string $key): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, '/pix/key/'.$key.'/claim', [], OperationResponseDTO::class);
    }

    /**
     * Confirma a portabilidade da chave Pix.
     *
     * @param  string  $id  ID da solicitação de portabilidade
     */
    public function confirmClaim(string $id): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id.'/claim/confirm', [], OperationResponseDTO::class);
    }

    /**
     * Cancela uma solicitação de portabilidade.
     *
     * @param  string  $id  ID da solicitação de portabilidade
     */
    public function cancelClaim(string $id): OperationResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_RECEIVABLES, self::BASE_PATH.'/'.$id.'/claim/cancel', [], OperationResponseDTO::class);
    }
}
