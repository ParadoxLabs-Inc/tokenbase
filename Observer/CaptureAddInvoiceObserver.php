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

namespace ParadoxLabs\TokenBase\Observer;

/**
 * In core, invoice is not directly accessible from the payment. What's with that?.
 */
class CaptureAddInvoiceObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * Add invoice to payment info instance on capture
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $payment = $observer->getEvent()->getData('payment');
        $invoice = $observer->getEvent()->getData('invoice');

        if (!$payment->hasData('invoice')) {
            $payment->setData('invoice', $invoice);
        }
    }
}
