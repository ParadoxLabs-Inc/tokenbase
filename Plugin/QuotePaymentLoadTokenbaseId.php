<?php declare(strict_types=1);
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
 *
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Plugin;

use Magento\Quote\Api\Data\PaymentExtensionFactory;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\Quote;
use ParadoxLabs\TokenBase\Helper\Data;

/**
 * QuotePaymentLoadTokenbaseId Plugin
 *
 * Load tokenbase_id to ExtensionAttributes for quote.
 */
class QuotePaymentLoadTokenbaseId
{
    /**
     * @param PaymentExtensionFactory $quotePaymentExtensionFactory
     * @param Data $helper
     */
    public function __construct(
        protected readonly PaymentExtensionFactory $quotePaymentExtensionFactory,
        protected readonly Data $helper
    ) {
    }

    /**
     * @param Quote $quote
     * @return void
     */
    private function setExtensionAttributeValue(Quote $quote)
    {
        $payment = $quote->getPayment();
        if ($payment instanceof PaymentInterface === false) {
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
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterLoad(
        Quote $subject,
        Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterLoadActive(
        Quote $subject,
        Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterLoadByCustomer(
        Quote $subject,
        Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param Quote $subject
     * @param Quote $result
     * @return Quote
     */
    public function afterLoadByIdWithoutStore(
        Quote $subject,
        Quote $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }
}
