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

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * Allower zero subtotal checkout for TokenBase methods
 */
class ZeroTotal
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
     * Override zero subtotal check to allow if tokenbase method.
     *
     * @param \Magento\Payment\Model\Checks\ZeroTotal $subject
     * @param \Closure $proceed
     * @param \Magento\Payment\Model\MethodInterface $paymentMethod
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     */
    public function aroundIsApplicable(
        \Magento\Payment\Model\Checks\ZeroTotal $subject,
        \Closure $proceed,
        \Magento\Payment\Model\MethodInterface $paymentMethod,
        \Magento\Quote\Model\Quote $quote
    ) {
        $returnValue = $proceed($paymentMethod, $quote);
        
        if ($returnValue !== true && in_array($paymentMethod->getCode(), $this->helper->getActiveMethods())) {
            return true;
        }
        
        return $returnValue;
    }
}
