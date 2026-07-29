<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\PixAutomatico;

use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para gerenciamento de Instruções de Pagamento (Payment Intents) do Pix Automático.
 *
 * Payment Intents representam instruções individuais de pagamento dentro
 * de uma autorização Pix Automático, com suporte a cancelamento e retentativas.
 *
 * @example
 * ```php
 * Transfeera::pixAutomaticoPaymentIntents()->create('auth_id', [...]);
 * Transfeera::pixAutomaticoPaymentIntents()->list('auth_id');
 * Transfeera::pixAutomaticoPaymentIntents()->resendRetry('pi_123');
 * ```
 */
class PaymentIntentResource extends BaseResource
{
    private const string BASE_PATH = '/pix/automatic/payment_intents';

    /**
     * Cria uma nova instrução de pagamento dentro de uma autorização.
     *
     * @param  string  $authorizationId  ID da autorização Pix Automático
     * @param  array<string, mixed>  $data  Dados da instrução de pagamento
     * @return array<string, mixed>
     */
    public function create(string $authorizationId, array $data): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            array_merge($data, ['authorization_id' => $authorizationId]),
            $this->accountId,
        );
    }

    /**
     * Lista as instruções de pagamento.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH,
            $filters,
            $this->accountId,
        );
    }

    /**
     * Consulta uma instrução de pagamento pelo ID.
     *
     * @param  string  $id  ID da instrução de pagamento
     * @return array<string, mixed>
     */
    public function get(string $id): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id,
            accountId: $this->accountId,
        );
    }

    /**
     * Cancela uma instrução de pagamento pendente.
     *
     * @param  string  $id  ID da instrução de pagamento
     * @return array<string, mixed>
     */
    public function cancel(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id . '/cancellations',
            accountId: $this->accountId,
        );
    }

    /**
     * Consulta o cancelamento de uma instrução de pagamento.
     *
     * @param  string  $id               ID da instrução de pagamento
     * @param  string  $cancellationId   ID do cancelamento
     * @return array<string, mixed>
     */
    public function getCancellation(string $id, string $cancellationId): array
    {
        return $this->connector->get(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id . '/cancellations/' . $cancellationId,
            accountId: $this->accountId,
        );
    }

    /**
     * Reenvia uma retentativa de cobrança para uma instrução com falha.
     *
     * @param  string  $id  ID da instrução de pagamento
     * @return array<string, mixed>
     */
    public function resendRetry(string $id): array
    {
        return $this->connector->post(
            Connector::DOMAIN_PAYMENTS,
            self::BASE_PATH . '/' . $id . '/retry',
            accountId: $this->accountId,
        );
    }
}
