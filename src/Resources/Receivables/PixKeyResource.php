<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Receivables;

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
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            accountId: $this->accountId,
        );
    }

    /**
     * Consulta uma chave Pix pelo ID.
     *
     * @param  string  $id  ID da chave Pix
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id,
            accountId: $this->accountId,
        );
    }

    /**
     * Cria uma nova chave Pix.
     *
     * @param  array{type: string, value: string}  $data  Dados da chave (type: cpf, cnpj, email, phone, evp)
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $data,
            $this->accountId,
        );
    }

    /**
     * Remove uma chave Pix.
     *
     * @param  string  $id  ID da chave Pix
     * @return array<string, mixed>
     */
    public function delete(string $id): array
    {
        return $this->connector->delete(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id,
            $this->accountId,
        );
    }

    /**
     * Reenvia o código de verificação para a chave Pix.
     *
     * @param  string  $id  ID da chave Pix
     * @return array<string, mixed>
     */
    public function resendVerificationCode(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/resendVerificationCode',
            accountId: $this->accountId,
        );
    }

    /**
     * Verifica uma chave Pix com o código recebido.
     *
     * @param  string  $id  ID da chave Pix
     * @param  string  $code  Código de verificação
     * @return array<string, mixed>
     */
    public function verify(string $id, string $code): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/verify',
            ['code' => $code],
            $this->accountId,
        );
    }

    /**
     * Inicia o processo de portabilidade/reivindicação de chave Pix.
     *
     * A chave é passada na URL: POST /pix/key/{key}/claim
     *
     * @param  string  $key  Chave Pix a ser reivindicada
     * @return array<string, mixed>
     */
    public function claim(string $key): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            '/pix/key/'.$key.'/claim',
            accountId: $this->accountId,
        );
    }

    /**
     * Confirma a portabilidade da chave Pix.
     *
     * @param  string  $id  ID da solicitação de portabilidade
     * @return array<string, mixed>
     */
    public function confirmClaim(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/claim/confirm',
            accountId: $this->accountId,
        );
    }

    /**
     * Cancela uma solicitação de portabilidade.
     *
     * @param  string  $id  ID da solicitação de portabilidade
     * @return array<string, mixed>
     */
    public function cancelClaim(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH.'/'.$id.'/claim/cancel',
            accountId: $this->accountId,
        );
    }
}
