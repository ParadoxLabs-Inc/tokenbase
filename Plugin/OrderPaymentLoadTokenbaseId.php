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

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * OrderPaymentLoadTokenbaseId Plugin
 *
 * Load tokenbase_id to ExtensionAttributes for order.
 */
class OrderPaymentLoadTokenbaseId
{
    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionFactory
     */
    protected $orderPaymentExtensionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderInterface $subject
     * @param \Closure $proceed
     * @param int $modelId
     * @param null $field
     * @return \Magento\Sales\Api\Data\OrderInterface
     */
    public function aroundLoad(
        \Magento\Sales\Api\Data\OrderInterface $subject,
        \Closure $proceed,
        $modelId,
        $field = null
    ) {
        /** @var \Magento\Sales\Api\Data\OrderInterface $cart */
        $order = $proceed($modelId, $field);

        $payment = $order->getPayment();
        if (!($payment instanceof \Magento\Sales\Api\Data\OrderPaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return $order;
        }

        $paymentExtension = $payment->getExtensionAttributes();
        if ($paymentExtension === null) {
            $paymentExtension = $this->orderPaymentExtensionFactory->create();
        }
        $paymentExtension->setTokenbaseId($payment->getData('tokenbase_id'));

        $payment->setExtensionAttributes($paymentExtension);

        return $order;
    }
}
