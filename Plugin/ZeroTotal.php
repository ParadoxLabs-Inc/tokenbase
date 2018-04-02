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
 * Allow zero subtotal checkout for TokenBase methods
 */
class ZeroTotal
{
    /**
     * Payment method codes that don't support $0 checkout whatsoever
     */
    protected static $noZeroSubtotalSupportMethods = [
        'braintree',
        'braintree_cc_vault',
    ];

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
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

        // This plugin can fire before the quote is actually initialized during admin checkout. We must ensure that
        // does not occur. Check active methods iff quote has an ID.
        if ($returnValue !== true && $quote->getId() > 0) {
            return $this->zeroSubtotalAllowed($paymentMethod->getCode());
        }

        return $returnValue;
    }

    /**
     * If this is a Vault-enabled method, or a TokenBase method, $0 checkout is actually okay.
     *
     * @param string $methodCode
     * @return bool
     */
    public function zeroSubtotalAllowed($methodCode)
    {
        // Blacklist?
        if (in_array($methodCode, $this->getNoZeroSubtotalSupportMethodCodes())) {
            return false;
        }

        // Unlisted Vault or TokenBase?
        $vaultMethodActive = (int)$this->scopeConfig->getValue(
            'payment/' . $methodCode . '_cc_vault/active',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($vaultMethodActive === 1 || in_array($methodCode, $this->helper->getActiveMethods())) {
            return true;
        }

        return false;
    }

    /**
     * Get payment method codes that don't support $0 checkout whatsoever
     *
     * @return string[]
     */
    public function getNoZeroSubtotalSupportMethodCodes()
    {
        return static::$noZeroSubtotalSupportMethods;
    }
}
