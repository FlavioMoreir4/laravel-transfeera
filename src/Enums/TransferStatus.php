<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Enums;

/**
 * Situações possíveis de uma transferência dentro de um lote.
 */
enum TransferStatus: string
{
    case Pending = 'pending';
    case Success = 'success';
    case Error = 'error';
    case Processing = 'processing';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Success => 'Confirmada',
            self::Error => 'Erro',
            self::Processing => 'Processando',
            self::Cancelled => 'Cancelada',
            self::Refunded => 'Estornada',
        };
    }
}
