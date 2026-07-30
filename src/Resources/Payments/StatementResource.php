<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Resources\Payments;

use FlavioMoreir4\Transfeera\DTOs\Response\StatementReportResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\StatementResponseDTO;
use FlavioMoreir4\Transfeera\DTOs\Response\StatementWithdrawResponseDTO;
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
     */
    public function withdraw(array $data): StatementWithdrawResponseDTO
    {
        return $this->postDTO(self::DOMAIN, '/statement/withdraw', $data, StatementWithdrawResponseDTO::class);
    }

    /**
     * Solicita um relatório de extrato.
     *
     * @param  array<string, mixed>  $params  Parâmetros (data_inicio, data_fim, etc.)
     */
    public function requestReport(array $params = []): StatementReportResponseDTO
    {
        return $this->postDTO(self::DOMAIN, '/statement_report', $params, StatementReportResponseDTO::class);
    }

    /**
     * Consulta um relatório de extrato pelo ID.
     *
     * @param  string  $reportId  Identificador do relatório
     */
    public function getReport(string $reportId): StatementReportResponseDTO
    {
        return $this->getDTO(self::DOMAIN, "/statement_report/{$reportId}", [], StatementReportResponseDTO::class);
    }
}
