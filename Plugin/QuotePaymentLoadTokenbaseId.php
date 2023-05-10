<?php
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
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
        if ($payment instanceof \Magento\Quote\Api\Data\PaymentInterface === false) {
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
