<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade para o SDK Transfeera.
 *
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\BatchResource batches(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\TransferResource transfers(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\BilletResource billets(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\BankResource banks(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\StatementResource statement(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\RecurrenceResource recurrences(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Payments\PixResource pix(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Receivables\PixKeyResource pixKeys(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Receivables\PixQrCodeResource pixQrCodes(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Receivables\PixCashInResource pixCashIn(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Receivables\ChargeResource charges(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\Receivables\PaymentLinkResource paymentLinks(?string $accountId = null)
 * @method static array getConfig()
 *
 * @see \FlavioMoreir4\Transfeera\TransfeeraClient
 */
class Transfeera extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'transfeera';
    }
}
