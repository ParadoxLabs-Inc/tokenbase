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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * CheckoutFailureVoidTransactionObserver Observer
 */
class CheckoutFailureVoidTransactionObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \ParadoxLabs\TokenBase\Model\Method\Factory
     */
    protected $methodFactory;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \ParadoxLabs\TokenBase\Model\Method\Factory $methodFactory
    ) {
        $this->helper = $helper;
        $this->methodFactory = $methodFactory;
    }

    /**
     * If checkout failed, void any transaction that went through.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getData('order');

        if ($order instanceof \Magento\Sales\Model\Order === false
            || $order->getPayment() instanceof \Magento\Sales\Model\Order\Payment === false
            || empty($order->getPayment()->getLastTransId())
            || in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === false
            || $order->getData('_tokenbase_saved_order') === true) {
            return;
        }

        try {
            $method  = $this->methodFactory->getMethodInstance($order->getPayment()->getMethod());
            $gateway = $method->gateway();

            $response = $gateway->void(
                $order->getPayment(),
                $order->getPayment()->getLastTransId()
            );

            $this->helper->log(
                $order->getPayment()->getMethod(),
                'Auto-voided transaction due to checkout failure: ' . $response->toJson()
            );
        } catch (\Exception $e) {
            // Log and ignore any errors; we don't want to throw them in this context.
            $this->helper->log(
                $order->getPayment()->getMethod(),
                'Checkout post-failure void failed: ' . $e->getMessage()
            );
        }
    }
}
