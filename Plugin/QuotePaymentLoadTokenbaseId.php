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
     * @param \Magento\Quote\Model\Quote $quote
     * @return void
     */
    private function setExtensionAttributeValue(\Magento\Quote\Model\Quote $quote)
    {
        $payment = $quote->getPayment();
        if (!($payment instanceof \Magento\Quote\Api\Data\PaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return;
        }

        $paymentExtension = $payment->getExtensionAttributes();
        if ($paymentExtension === null) {
            $paymentExtension = $this->quotePaymentExtensionFactory->create();
        }
        $paymentExtension->setTokenbaseId($payment->getData('tokenbase_id'));

        $payment->setExtensionAttributes($paymentExtension);
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterLoad(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Quote\Model\Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterLoadActive(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Quote\Model\Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterLoadByCustomer(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Quote\Model\Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param \Magento\Quote\Model\Quote $result
     * @return \Magento\Quote\Model\Quote
     */
    public function afterLoadByIdWithoutStore(
        \Magento\Quote\Model\Quote $subject,
        \Magento\Quote\Model\Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }
}
