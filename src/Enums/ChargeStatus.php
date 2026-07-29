<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Enums;

/**
 * Situações possíveis de uma cobrança (boleto + Pix).
 */
enum ChargeStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case PartiallyPaid = 'partially_paid';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case Overdue = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Paid => 'Paga',
            self::PartiallyPaid => 'Paga parcialmente',
            self::Expired => 'Vencida',
            self::Cancelled => 'Cancelada',
            self::Refunded => 'Estornada',
            self::Overdue => 'Em atraso',
        };
    }
}
