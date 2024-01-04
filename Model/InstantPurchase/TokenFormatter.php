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

class TokenFormatter extends \Magento\InstantPurchase\PaymentMethodIntegration\SimplePaymentTokenFormatter
{
    /**
     * Creates string presentation of payment token.
     *
     * @param \Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken
     * @return string
     * @since 100.2.0
     */
    public function formatPaymentToken(\Magento\Vault\Api\Data\PaymentTokenInterface $paymentToken): string
    {
        $methodTitle = parent::formatPaymentToken($paymentToken);

        if ($paymentToken instanceof \ParadoxLabs\TokenBase\Api\Data\CardInterface) {
            return sprintf('%s: %s', $methodTitle, $paymentToken->getLabel());
        }

        return $methodTitle;
    }
}
