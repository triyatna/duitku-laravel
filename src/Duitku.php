<?php

namespace Triyatna\DuitkuLaravel;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array getPaymentMethods(int $amount)
 * @method static array createInvoice(int $paymentAmount, string $paymentMethod, string $merchantOrderId, string $productDetails, string $customerVaName, string $email, string|null $phoneNumber, string $callbackUrl, string $returnUrl, int $expiryPeriod = 1440, array|null $itemDetails = null, array|null $customerDetail = null)
 * @method static array checkTransactionStatus(string $merchantOrderId)
 * @method static \Illuminate\Http\JsonResponse handleCallback()
 *
 * @see \Triyatna\DuitkuLaravel\Duitku
 */
class Duitku extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'duitkuhelper';
    }
}
