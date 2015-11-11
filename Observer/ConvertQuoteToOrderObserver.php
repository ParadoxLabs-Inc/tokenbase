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

use Magento\Framework\Event\Observer;

/**
 * Custom data field conversion -- quote to order, etc, etc.
 */
class ConvertQuoteToOrderObserver extends ConvertAbstract implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Copy fields from quote payment to order payment
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /** @var \Magento\Sales\Model\Order $order */
        $order  = $observer->getEvent()->getData('order');

        // Do the magic. Yeah, this is it.
        $this->copyData(
            $quote->getPayment(),
            $order->getPayment()
        );
    }
}
