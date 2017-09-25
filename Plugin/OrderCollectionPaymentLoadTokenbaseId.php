<?php
/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 */

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * OrderCollectionPaymentLoadTokenbaseId Class
 *
 * Load tokenbase_id to ExtensionAttributes for order.
 */
class OrderCollectionPaymentLoadTokenbaseId
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
     * @param \Magento\Sales\Api\Data\OrderSearchResultInterface $subject
     * @param \Magento\Framework\DataObject $order
     * @return mixed
     */
    public function beforeAddItem(
        \Magento\Sales\Api\Data\OrderSearchResultInterface $subject,
        \Magento\Framework\DataObject $order
    ) {
        /** @var \Magento\Sales\Api\Data\OrderInterface $order */

        $payment = $order->getPayment();
        if (!($payment instanceof \Magento\Sales\Api\Data\OrderPaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return null;
        }

        $paymentExtension = $payment->getExtensionAttributes();
        if ($paymentExtension === null) {
            $paymentExtension = $this->orderPaymentExtensionFactory->create();
        }
        $paymentExtension->setTokenbaseId($payment->getData('tokenbase_id'));

        $payment->setExtensionAttributes($paymentExtension);

        return [
            $order
        ];
    }
}
