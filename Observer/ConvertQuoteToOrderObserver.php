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
 * Custom data field conversion -- quote to order, etc, etc.
 */
class ConvertQuoteToOrderObserver extends ConvertAbstract implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * ConvertQuoteToOrderObserver constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->resource = $resource;
    }

    /**
     * Perform pre-order-placement actions.
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

        if (in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) !== true) {
            return;
        }

        /**
         * Copy fields from quote payment to order payment. If using GraphQL, set tokenbase_id.
         */
        $payment = $quote->getPayment();
        if (!$payment->getData('tokenbase_id')) {
            $paymentAttr = $payment->getExtensionAttributes();
            if ($paymentAttr) {
                $payment->setData('tokenbase_id', $paymentAttr->getTokenbaseId());
            }
        }

        $this->copyData(
            $payment,
            $order->getPayment()
        );

        /**
         * Persist increment_id -- if it's changed, it's new
         */
        if ($quote->getId()
            && empty($quote->getOrigData('reserved_order_id'))
            && !empty($quote->getReservedOrderId())) {
            // Save quote.reserved_order_id directly to the DB with no other interaction -- only efficient option.
            $connection = $this->resource->getConnection('checkout');
            $connection->update(
                $this->resource->getTableName('quote'),
                [
                    'reserved_order_id' => $quote->getReservedOrderId(),
                ],
                [
                    'entity_id=?' => $quote->getId(),
                ]
            );
        }
    }
}
