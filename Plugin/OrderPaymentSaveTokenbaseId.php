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
 * OrderPaymentSaveTokenbaseId Plugin
 *
 * Save tokenbase_id from ExtensionAttributes for order.
 */
class OrderPaymentSaveTokenbaseId
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Sales\Api\OrderRepositoryInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param bool $saveOptions
     * @return \Magento\Sales\Api\Data\OrderInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundSave(
        \Magento\Sales\Api\OrderRepositoryInterface $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\OrderInterface $order,
        $saveOptions = false
    ) {
        /** @var \Magento\Sales\Api\Data\OrderInterface $result */
        $result = $proceed($order, $saveOptions);

        $payment = $order->getPayment();
        if (!($payment instanceof \Magento\Sales\Api\Data\OrderPaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return $result;
        }

        /** @var \Magento\Sales\Api\Data\OrderPaymentExtensionInterface $extendedAttributes */
        $extendedAttributes = $payment->getExtensionAttributes();
        if ($extendedAttributes === null) {
            return $result;
        }

        $tokenbaseId = $extendedAttributes->getTokenbaseId();

        if ($tokenbaseId !== null
            && $tokenbaseId != $payment->getOrigData('tokenbase_id')
            && $tokenbaseId != $payment->getData('tokenbase_id')) {
            $payment->setData('tokenbase_id', $tokenbaseId);
        }

        return $result;
    }
}
