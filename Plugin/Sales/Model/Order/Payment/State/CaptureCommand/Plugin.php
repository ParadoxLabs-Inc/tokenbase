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

namespace ParadoxLabs\TokenBase\Plugin\Sales\Model\Order\Payment\State\CaptureCommand;

/**
 * Plugin Class
 */
class Plugin
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Plugin constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Override order status with payment method status setting in certain circumstances.
     *
     * @param \Magento\Sales\Model\Order\Payment\State\CaptureCommand $subject
     * @param \Closure $proceed
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @param string|float $amount
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @return \Magento\Framework\Phrase
     */
    public function aroundExecute(
        \Magento\Sales\Model\Order\Payment\State\CaptureCommand $subject,
        \Closure $proceed,
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment,
        $amount,
        \Magento\Sales\Api\Data\OrderInterface $order
    ) {
        $returnValue = $proceed($payment, $amount, $order);
        
        // If we're setting the order state to default processing on authorize/capture, inject our status.
        if ($this->canSetOrderStatus($order, $payment)) {
            $status = $this->scopeConfig->getValue(
                'payment/' . $payment->getMethod() . '/order_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $order->setStatus($status);
        }
        
        return $returnValue;
    }

    /**
     * Determine whether order is in the right state for status override.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return bool
     */
    public function canSetOrderStatus(
        \Magento\Sales\Api\Data\OrderInterface $order,
        \Magento\Sales\Api\Data\OrderPaymentInterface $payment
    ) {
        // Do not allow status change if transaction is incomplete
        if ($payment->getIsTransactionPending()) {
            return false;
        }

        // Or order was held for fraud
        if ($payment->getIsFraudDetected()) {
            return false;
        }

        // Or order state is not processing (possibly redundant)
        if ($order->getState() !== \Magento\Sales\Model\Order::STATE_PROCESSING) {
            return false;
        }

        // Or payment method is not a tokenbase one
        if (!in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return false;
        }

        // Or payment action is not authorize_capture (meaning this capture is not the first transaction)
        $paymentAction = $this->scopeConfig->getValue(
            'payment/' . $payment->getMethod() . '/payment_action',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if ($paymentAction !== 'authorize_capture') {
            return false;
        }

        return true;
    }
}
