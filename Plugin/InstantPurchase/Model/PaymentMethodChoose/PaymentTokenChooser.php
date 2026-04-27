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

use ParadoxLabs\TokenBase\Model\Card;
use Magento\Customer\Model\Customer;
use Magento\InstantPurchase\Model\PaymentMethodChoose\PaymentTokenChooserInterface;
use Magento\Store\Model\Store;
use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\PaymentTokenManagement;

class PaymentTokenChooser
{
    /**
     * PaymentTokenChooser constructor.
     *
     * @param PaymentTokenManagement $paymentTokenManagement
     */
    public function __construct(protected readonly PaymentTokenManagement $paymentTokenManagement)
    {
    }

    /**
     * Add TokenBase cards to the available options for Instant Purchase.
     *
     * @param PaymentTokenChooserInterface $subject
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

            /** @var Card $result */
            $result = current($tokens) ?: null;
        }

        return $result;
    }
}
