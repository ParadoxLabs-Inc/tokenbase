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
 * QuotePaymentLoadTokenbaseId Plugin
 *
 * Load tokenbase_id to ExtensionAttributes for quote.
 */
class QuotePaymentLoadTokenbaseId
{
    /**
     * @var \Magento\Quote\Api\Data\PaymentExtensionFactory
     */
    protected $quotePaymentExtensionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @param \Magento\Quote\Api\Data\PaymentExtensionFactory $quotePaymentExtensionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Quote\Api\Data\PaymentExtensionFactory $quotePaymentExtensionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->quotePaymentExtensionFactory = $quotePaymentExtensionFactory;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Quote\Api\Data\CartInterface $subject
     * @param \Closure $proceed
     * @param int $modelId
     * @param null $field
     * @return \Magento\Quote\Api\Data\CartInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundLoad(
        \Magento\Quote\Api\Data\CartInterface $subject,
        \Closure $proceed,
        $modelId,
        $field = null
    ) {
        /** @var \Magento\Quote\Api\Data\CartInterface $quote */
        $quote = $proceed($modelId, $field);

        $payment = $quote->getPayment();
        if (!($payment instanceof \Magento\Quote\Api\Data\PaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return $quote;
        }

        $paymentExtension = $payment->getExtensionAttributes();
        if ($paymentExtension === null) {
            $paymentExtension = $this->quotePaymentExtensionFactory->create();
        }
        $paymentExtension->setTokenbaseId($payment->getData('tokenbase_id'));

        $payment->setExtensionAttributes($paymentExtension);

        return $quote;
    }
}
