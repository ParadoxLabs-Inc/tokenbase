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
 * QuotePaymentSaveTokenbaseId Plugin
 *
 * Save tokenbase_id from ExtensionAttributes for quote.
 */
class QuotePaymentSaveTokenbaseId
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
     * @param \Magento\Quote\Api\CartRepositoryInterface $subject
     * @param \Closure $proceed
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @param bool $saveOptions
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function aroundSave(
        \Magento\Quote\Api\CartRepositoryInterface $subject,
        \Closure $proceed,
        \Magento\Quote\Api\Data\CartInterface $quote,
        $saveOptions = false
    ) {
        /** @var \Magento\Quote\Api\Data\CartInterface $result */
        $result = $proceed($quote, $saveOptions);

        $payment = $quote->getPayment();
        if (!($payment instanceof \Magento\Quote\Api\Data\PaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return $result;
        }

        /** @var \Magento\Quote\Api\Data\PaymentExtensionInterface $extendedAttributes */
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
