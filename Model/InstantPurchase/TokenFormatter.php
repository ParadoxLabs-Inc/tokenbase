<?php declare(strict_types=1);
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <info@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Model\InstantPurchase;

use Magento\InstantPurchase\PaymentMethodIntegration\SimplePaymentTokenFormatter;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use ParadoxLabs\TokenBase\Api\Data\CardInterface;

class TokenFormatter extends SimplePaymentTokenFormatter
{
    /**
     * Creates string presentation of payment token.
     *
     * @param PaymentTokenInterface $paymentToken
     * @return string
     * @since 100.2.0
     */
    #[\Override]
    public function formatPaymentToken(PaymentTokenInterface $paymentToken): string
    {
        $methodTitle = parent::formatPaymentToken($paymentToken);

        if ($paymentToken instanceof CardInterface) {
            return sprintf('%s: %s', $methodTitle, $paymentToken->getLabel());
        }

        return $methodTitle;
    }
}
