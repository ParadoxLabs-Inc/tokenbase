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
 * MultishippingAllowZeroTotalCheckoutObserver Class
 */
class MultishippingAllowZeroTotalCheckoutObserver implements \Magento\Framework\Event\ObserverInterface
{
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
     * On multishipping checkout, Magento forces the payment method to 'free' if grand total is $0. If the order payment
     * is 'free' and the quote payment is not, overwrite it back to the quote payment method.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getData('order');
        $quote = $observer->getEvent()->getData('quote');

        // If the order payment method was forced to free...
        if ($order->getPayment()->getMethod() === 'free' && $quote->getPayment()->getMethod() !== 'free') {
            $vaultMethodActive = (int)$this->scopeConfig->getValue(
                'payment/' . $quote->getPayment()->getMethod() . '_cc_vault/active',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            // And if this is a Vault-enabled method, or a TokenBase method, allow.
            if ($vaultMethodActive === 1
                || in_array($quote->getPayment()->getMethod(), $this->helper->getActiveMethods())) {
                $order->getPayment()->setMethod($quote->getPayment()->getMethod());
            }
        }
    }
}
