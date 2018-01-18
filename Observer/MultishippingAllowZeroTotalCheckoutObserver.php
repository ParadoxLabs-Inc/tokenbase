<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * MultishippingAllowZeroTotalCheckoutObserver Class
 */
class MultishippingAllowZeroTotalCheckoutObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * On multishipping checkout, Magento forces the payment method to 'free' if grand total is $0. If the order payment
     * is 'free' and the quote payment is not, overwrite it back to the quote payment method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        // If the order payment method was forced to free...
        if ($order->getPayment()->getMethod() === 'free' && $quote->getPayment()->getMethod() !== 'free') {
            $vaultMethodActive = (int)$this->scopeConfig->getValue(
                'payment/' . $quote->getPayment()->getMethod() . '_cc_vault/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // And if this is a Vault-enabled method, or a TokenBase method, allow.
            if ($vaultMethodActive === 1
                || in_array($quote->getPayment()->getMethod(), $this->helper->getActiveMethods())) {
                $order->getPayment()->setMethod($quote->getPayment()->getMethod());
            }
        }
    }
}
