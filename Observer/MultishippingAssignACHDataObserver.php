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
 * MultishippingAssignACHDataObserver Class
 */
class MultishippingAssignACHDataObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    protected $eventManager;

    /**
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Event\ManagerInterface $eventManager
    ) {
        $this->helper = $helper;
        $this->request = $request;
        $this->eventManager = $eventManager;
    }

    /**
     * On multishipping checkout, Magento explicitly carries across cc_number/cc_cid but nothing else. We need to
     * persist ACH fields as well.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        $post  = $this->request->getPost();

        if (in_array($quote->getPayment()->getMethod(), $this->helper->getActiveMethods(), true)
            && !empty($post['payment']['echeck_account_no'])) {
            $this->eventManager->dispatch(
                'payment_method_assign_data_' . $quote->getPayment()->getMethod(),
                [
                    'method' => $this->helper->getMethodInstance($quote->getPayment()->getMethod()),
                    'payment_model' => $quote->getPayment(),
                    'data' => new \Magento\Framework\DataObject($post['payment']),
                ]
            );
        }
    }
}
