<?php

declare(strict_types=1);

namespace FlavioMoreir4\Transfeera\Facades;

use FlavioMoreir4\Transfeera\Resources\Accounts\AccountResource;
use FlavioMoreir4\Transfeera\Resources\ContaCerta\ValidationResource;
use FlavioMoreir4\Transfeera\Resources\Infractions\InfractionResource;
use FlavioMoreir4\Transfeera\Resources\Payments\BankResource;
use FlavioMoreir4\Transfeera\Resources\Payments\BatchResource;
use FlavioMoreir4\Transfeera\Resources\Payments\BilletResource;
use FlavioMoreir4\Transfeera\Resources\Payments\PixResource;
use FlavioMoreir4\Transfeera\Resources\Payments\RecurrenceResource;
use FlavioMoreir4\Transfeera\Resources\Payments\StatementResource;
use FlavioMoreir4\Transfeera\Resources\Payments\TransferResource;
use FlavioMoreir4\Transfeera\Resources\PixAutomatico\AuthorizationResource;
use FlavioMoreir4\Transfeera\Resources\PixAutomatico\PaymentIntentResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\ChargeResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PaymentLinkResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixCashInResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixKeyResource;
use FlavioMoreir4\Transfeera\Resources\Receivables\PixQrCodeResource;
use FlavioMoreir4\Transfeera\Resources\Webhooks\ContaCertaWebhookResource;
use FlavioMoreir4\Transfeera\Resources\Webhooks\PaymentsWebhookResource;
use FlavioMoreir4\Transfeera\Resources\Webhooks\ReceivablesWebhookResource;
use FlavioMoreir4\Transfeera\TransfeeraClient;
use Illuminate\Support\Facades\Facade;

/**
 * Facade para o SDK Transfeera.
 *
 * @method static BatchResource batches(?string $accountId = null)
 * @method static TransferResource transfers(?string $accountId = null)
 * @method static BilletResource billets(?string $accountId = null)
 * @method static BankResource banks(?string $accountId = null)
 * @method static StatementResource statement(?string $accountId = null)
 * @method static RecurrenceResource recurrences(?string $accountId = null)
 * @method static PixResource pix(?string $accountId = null)
 * @method static PixKeyResource pixKeys(?string $accountId = null)
 * @method static PixQrCodeResource pixQrCodes(?string $accountId = null)
 * @method static PixCashInResource pixCashIn(?string $accountId = null)
 * @method static ChargeResource charges(?string $accountId = null)
 * @method static PaymentLinkResource paymentLinks(?string $accountId = null)
 * @method static array<int, mixed> getConfig()
 * @method static AuthorizationResource pixAutomaticoAuthorizations(?string $accountId = null)
 * @method static PaymentIntentResource pixAutomaticoPaymentIntents(?string $accountId = null)
 * @method static PaymentsWebhookResource paymentsWebhooks(?string $accountId = null)
 * @method static ReceivablesWebhookResource receivablesWebhooks(?string $accountId = null)
 * @method static ContaCertaWebhookResource contaCertaWebhooks(?string $accountId = null)
 * @method static ValidationResource contaCertaValidations(?string $accountId = null)
 * @method static \FlavioMoreir4\Transfeera\Resources\ContaCerta\BankResource contaCertaBanks(?string $accountId = null)
 * @method static AccountResource accounts(?string $accountId = null)
 * @method static InfractionResource infractions(?string $accountId = null)
 *
 * @see TransfeeraClient
 */
class Transfeera extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'transfeera';
    }
}
