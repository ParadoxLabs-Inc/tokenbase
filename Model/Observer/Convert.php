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

namespace ParadoxLabs\TokenBase\Model\Observer;

/**
 * Custom data field conversion -- quote to order, etc, etc.
 */
class Convert
{
    /**
     * Copy tokenbase_id from quote payment to order payment
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function quoteToOrder(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /** @var \Magento\Sales\Model\Order $order */
        $order  = $observer->getEvent()->getData('order');

        // Do the magic. Yeah, this is it.
        $order->getPayment()->setData('tokenbase_id', $quote->getPayment()->getData('tokenbase_id'));

        return $this;
    }

    /**
     * Copy tokenbase_id from order payment to quote payment
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return $this
     */
    public function orderToQuote(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /** @var \Magento\Sales\Model\Order $order */
        $order  = $observer->getEvent()->getData('order');

        // Do the magic. Yeah, this is it.
        $quote->getPayment()->setData('tokenbase_id', $order->getPayment()->getData('tokenbase_id'));

        return $this;
    }
}
