<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @author      Ryan Hoerr <magento@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * In core, invoice is not directly accessible from the payment. What's with that?.
 */
class CaptureAddInvoiceObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Add invoice to payment info instance on capture
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');
        $invoice = $observer->getEvent()->getData('invoice');

        if (!$payment->hasData('invoice')) {
            $payment->setData('invoice', $invoice);
        }
    }
}
