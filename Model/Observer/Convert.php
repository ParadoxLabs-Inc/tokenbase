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
     * @var string[]
     */
    protected $fields = [
        'tokenbase_id',
        'echeck_account_name',
        'echeck_bank_name',
        'echec_account_type',
        'echeck_routing_number',
        'echeck_routing_no',
        'echeck_account_no',
    ];

    /**
     * Copy fields from quote payment to order payment
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
        foreach ($this->fields as $field) {
            $order->getPayment()->setData($field, $quote->getPayment()->getData($field));
        }

        return $this;
    }

    /**
     * Copy fields from order payment to quote payment
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
        foreach ($this->fields as $field) {
            $quote->getPayment()->setData($field, $order->getPayment()->getData($field));
        }

        return $this;
    }
}
