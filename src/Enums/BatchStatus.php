<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Enums;

/**
 * Situações possíveis de um lote de pagamentos.
 */
enum BatchStatus: string
{
    /** Aguardando processamento */
    case Pending = 'pending';
    /** Processado com sucesso */
    case Processed = 'processed';
    /** Processado com erros */
    case ProcessedWithErrors = 'processed_with_errors';
    /** Cancelado */
    case Cancelled = 'cancelled';
    /** Em processamento */
    case Processing = 'processing';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Processed => 'Processado',
            self::ProcessedWithErrors => 'Processado com erros',
            self::Cancelled => 'Cancelado',
            self::Processing => 'Em processamento',
        };
    }
}
