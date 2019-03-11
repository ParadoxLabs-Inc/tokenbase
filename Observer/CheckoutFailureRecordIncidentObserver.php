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
 * CheckoutFailureRecordIncidentObserver Class
 */
class CheckoutFailureRecordIncidentObserver implements \Magento\Framework\Event\ObserverInterface
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
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Customer\Model\Session $customerSession *Proxy
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->helper          = $helper;
        $this->customerSession = $customerSession;
    }

    /**
     * On checkoutfailure, record the error if it's a TokenBase method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getData('quote');
        /** @var \Exception $exception */
        $exception = $observer->getData('exception');

        // Note: We skip AuthorizationException errors to not count attempts we block ourselves.
        // @see \ParadoxLabs\TokenBase\Observer\CheckoutCheckFailuresObserver
        if ($quote instanceof \Magento\Quote\Model\Quote
            && $exception instanceof \Exception
            && ($exception instanceof \Magento\Framework\Exception\AuthorizationException) === false
            && $quote->getPayment() instanceof \Magento\Quote\Model\Quote\Payment
            && in_array($quote->getPayment()->getMethod(), $this->helper->getAllMethods(), true)) {
            $this->recordSessionFailure($exception);
        }
    }

    /**
     * Record each save failure on their session. If they fail too many times in a given period, block access. This is
     * to help prevent credit card validation abuse, trying to store CCs until one works.
     *
     * @param \Exception $e
     * @return void
     */
    protected function recordSessionFailure(\Exception $e)
    {
        $failures = $this->customerSession->getData('tokenbase_failures');
        if (is_array($failures) === false) {
            $failures = [];
        }

        $failures[time()] = $e->getMessage();

        $this->customerSession->setData('tokenbase_failures', $failures);
    }
}
