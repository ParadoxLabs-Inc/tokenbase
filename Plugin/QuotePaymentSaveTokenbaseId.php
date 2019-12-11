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
     * @param \Magento\Quote\Api\Data\CartInterface $quote
     * @return array
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function beforeSave(
        \Magento\Quote\Api\CartRepositoryInterface $subject,
        \Magento\Quote\Api\Data\CartInterface $quote
    ) {
        /** @var \Magento\Quote\Model\Quote\Payment $payment */
        $payment = $quote->getPayment();
        if (!($payment instanceof \Magento\Quote\Api\Data\PaymentInterface)
            || !in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return [$quote];
        }

        /** @var \Magento\Quote\Api\Data\PaymentExtensionInterface $extendedAttributes */
        $extendedAttributes = $payment->getExtensionAttributes();
        if ($extendedAttributes === null) {
            $tokenbaseId = $payment->getData('tokenbase_id');
        } else {
            $tokenbaseId = $extendedAttributes->getTokenbaseId();
        }

        if ($tokenbaseId !== null
            && $tokenbaseId != $payment->getOrigData('tokenbase_id')
            && $tokenbaseId != $payment->getData('tokenbase_id')) {
            $payment->setData('tokenbase_id', $tokenbaseId);
        }

        return [$quote];
    }
}
