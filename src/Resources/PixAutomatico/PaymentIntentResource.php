<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\PixAutomatico;

use FlavioMoreir4\Transfeera\DTOs\Response\PaymentIntentResponseDTO;
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
     */
    public function create(string $authorizationId, array $data): PaymentIntentResponseDTO
    {
        return $this->postDTO(
            Connector::DOMAIN_PIX_AUTOMATICO,
            self::BASE_PATH,
            array_merge($data, ['authorization_id' => $authorizationId]),
            PaymentIntentResponseDTO::class,
        );
    }

    /**
     * Lista as instruções de pagamento.
     *
     * @param  array<string, mixed>  $filters  Filtros opcionais
     * @return array<int, PaymentIntentResponseDTO>
     */
    public function list(array $filters = []): array
    {
        return $this->getDTOList(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH, $filters, PaymentIntentResponseDTO::class);
    }

    /**
     * Consulta uma instrução de pagamento pelo ID.
     *
     * @param  string  $id  ID da instrução de pagamento
     */
    public function get(string $id): PaymentIntentResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id, [], PaymentIntentResponseDTO::class);
    }

    /**
     * Cancela uma instrução de pagamento.
     *
     * @param  string  $id  ID da instrução
     */
    public function cancel(string $id): PaymentIntentResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id.'/cancellations', [], PaymentIntentResponseDTO::class);
    }

    /**
     * Consulta o cancelamento de uma instrução de pagamento.
     *
     * @param  string  $id  ID da instrução
     * @param  string  $cancellationId  ID do cancelamento
     */
    public function getCancellation(string $id, string $cancellationId): PaymentIntentResponseDTO
    {
        return $this->getDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id.'/cancellations/'.$cancellationId, [], PaymentIntentResponseDTO::class);
    }

    /**
     * Reenvia uma retentativa de instrução de pagamento.
     *
     * @param  string  $id  ID da instrução
     */
    public function resendRetry(string $id): PaymentIntentResponseDTO
    {
        return $this->postDTO(Connector::DOMAIN_PIX_AUTOMATICO, self::BASE_PATH.'/'.$id.'/retry', [], PaymentIntentResponseDTO::class);
    }
}
