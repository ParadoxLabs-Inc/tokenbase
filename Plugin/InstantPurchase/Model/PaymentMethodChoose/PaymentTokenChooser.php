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

namespace ParadoxLabs\TokenBase\Plugin\InstantPurchase\Model\PaymentMethodChoose;

use Magento\Customer\Model\Customer;
use Magento\InstantPurchase\Model\PaymentMethodChoose\PaymentTokenChooserInterface;
use Magento\Store\Model\Store;
use Magento\Vault\Api\Data\PaymentTokenInterface;

class PaymentTokenChooser
{
    /**
     * @var \Magento\Vault\Model\PaymentTokenManagement
     */
    protected $paymentTokenManagement;

    /**
     * PaymentTokenChooser constructor.
     *
     * @param \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
     */
    public function __construct(
        \Magento\Vault\Model\PaymentTokenManagement $paymentTokenManagement
    ) {
        $this->paymentTokenManagement = $paymentTokenManagement;
    }

    /**
     * Add TokenBase cards to the available options for Instant Purchase.
     *
     * @param \Magento\InstantPurchase\Model\PaymentMethodChoose\PaymentTokenChooserInterface $subject
     * @param PaymentTokenInterface|null $result
     * @param Store $store
     * @param Customer $customer
     * @return PaymentTokenInterface|null
     */
    public function afterChoose(
        PaymentTokenChooserInterface $subject,
        $result,
        Store $store,
        Customer $customer
    ) {
        if ($result === null) {
            /**
             * Get customer's latest used active TokenBase card, if any
             */
            $tokens = $this->paymentTokenManagement->getVisibleAvailableTokens($customer->getId());

            /** @var \ParadoxLabs\TokenBase\Model\Card $result */
            $result = current($tokens) ?: null;
        }

        return $result;
    }
}
