<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\StatementResponseDTO;
use FlavioMoreir4\Transfeera\Http\Connector;
use FlavioMoreir4\Transfeera\Resources\Concerns\BaseResource;

/**
 * Resource para saldo e extrato da conta.
 *
 * @see https://docs.transfeera.dev/reference/statement
 */
class StatementResource extends BaseResource
{
    private const string DOMAIN = Connector::DOMAIN_PAYMENTS;

    /**
     * Consulta o saldo disponível na conta.
     */
    public function getBalance(): StatementResponseDTO
    {
        return $this->getDTO(self::DOMAIN, '/statement/balance', [], StatementResponseDTO::class);
    }

    /**
     * Resgata (saca) o saldo disponível para uma conta bancária.
     *
     * @param  array<string, mixed>  $data  Dados do resgate (valor, conta destino)
     * @return array<string, mixed>
     */
    public function withdraw(array $data): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/statement/withdraw',
            $data,
            $this->accountId,
        );
    }

    /**
     * Solicita um relatório de extrato.
     *
     * @param  array<string, mixed>  $params  Parâmetros (data_inicio, data_fim, etc.)
     * @return array<string, mixed>
     */
    public function requestReport(array $params = []): array
    {
        return $this->connector->post(
            self::DOMAIN,
            '/statement_report',
            $params,
            $this->accountId,
        );
    }

    /**
     * Consulta um relatório de extrato pelo ID.
     *
     * @param  string  $reportId  Identificador do relatório
     * @return array<string, mixed>
     */
    public function getReport(string $reportId): array
    {
        return $this->connector->get(
            self::DOMAIN,
            "/statement_report/{$reportId}",
            [],
            $this->accountId,
        );
    }
}
