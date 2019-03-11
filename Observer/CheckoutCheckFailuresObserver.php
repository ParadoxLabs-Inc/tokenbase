<?php
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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * CheckoutCheckFailuresObserver Class
 */
class CheckoutCheckFailuresObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * CheckoutCheckFailuresObserver constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * If customer has failed checkout more than X times within the last Y seconds, block them from further checkout
     * attempts. This is to prevent credit card testing on checkout, runaway txn charges, and an unhappy gateway.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\AuthorizationException
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getData('order');

        if ($order instanceof \Magento\Sales\Model\Order
            && $order->getPayment() instanceof \Magento\Sales\Model\Order\Payment
            && in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true)
            && $this->customerHasTooManyFailures()) {
            throw new \Magento\Framework\Exception\AuthorizationException(
                __('Checkout is currently unavailable due to excessive errors. Please contact us for assistance.')
            );
        }
    }

    /**
     * Determine whether the customer has more than the allowed number of recent failures.
     *
     * @return bool
     */
    protected function customerHasTooManyFailures()
    {
        $failures = $this->customerSession->getData('tokenbase_failures');

        // Number of failures to block after (default 5)
        $failureLimit = $this->scopeConfig->getValue(
            'checkout/tokenbase/failure_limit',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // Number of seconds to keep failures (default 86400, 1 day)
        $failureWindow = $this->scopeConfig->getValue(
            'checkout/tokenbase/failure_window',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if (is_array($failures) && count($failures) >= $failureLimit) {
            $countInWindow = 0;
            foreach ($failures as $time => $message) {
                if ($time > time() - $failureWindow) {
                    $countInWindow++;
                }
            }

            $this->helper->log(__METHOD__, $failures);

            if ($countInWindow >= $failureLimit) {
                return true;
            }
        }

        return false;
    }
}
