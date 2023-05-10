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
 * Refund Observer
 */
class ProcessRefundObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(\ParadoxLabs\TokenBase\Helper\Data $helper)
    {
        $this->helper = $helper;
    }

    /**
     * If we're doing a partial refund, don't mark it as fully refunded
     * unless the full amount is done.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $memo    = $observer->getEvent()->getData('creditmemo');
        $methods = $this->helper->getAllMethods();

        if (in_array($memo->getOrder()->getPayment()->getMethod(), $methods)
            && $memo->getInvoice()
            && $memo->getInvoice()->getBaseTotalRefunded() < $memo->getInvoice()->getBaseGrandTotal()) {
            $memo->getInvoice()->setIsUsedForRefund(false);
        }
    }
}
