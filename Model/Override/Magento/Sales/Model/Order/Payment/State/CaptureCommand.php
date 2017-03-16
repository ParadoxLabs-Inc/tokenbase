<?php
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

namespace ParadoxLabs\TokenBase\Model\Override\Magento\Sales\Model\Order\Payment\State;

/**
 * CaptureCommand Class
 */
class CaptureCommand extends \Magento\Sales\Model\Order\Payment\State\CaptureCommand
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
     * SetOrderStatus constructor
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
     * Overwrite order status if appropriate.
     *
     * @param \Magento\Sales\Model\Order $order
     * @param $status
     * @param $state
     * @return void
     */
    protected function setOrderStateAndStatus(\Magento\Sales\Model\Order $order, $status, $state)
    {
        // If we're setting the order state to default processing on authorize/capture, inject our status.
        if ($status === false && $state == \Magento\Sales\Model\Order::STATE_PROCESSING) {
            $methodCode = $order->getPayment()->getMethod();
            
            $paymentAction = $this->scopeConfig->getValue(
                'payment/' . $methodCode . '/payment_action',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // But only if it's our method, and this is the first payment action.
            if (in_array($methodCode, $this->helper->getAllMethods()) && $paymentAction === 'authorize_capture') {
                $status = $this->scopeConfig->getValue(
                    'payment/' . $methodCode . '/order_status',
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
            }
        }

        parent::setOrderStateAndStatus($order, $status, $state);
    }
}
