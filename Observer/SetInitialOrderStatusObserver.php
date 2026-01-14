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

namespace ParadoxLabs\TokenBase\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Helper\Data;

class SetInitialOrderStatusObserver implements ObserverInterface
{
    /**
     * Plugin constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected Data $helper
    ) {
    }

    /**
     * On order place, override the order status according to payment method configuration.
     *
     * All of this just to allow 'pending' and other off-state statuses to be chosen...
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $observer->getData('payment');

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();

        // If we're setting the order state to default processing on order placement, inject our status.
        if ($this->canSetOrderStatus($order, $payment)) {
            $status = $this->scopeConfig->getValue(
                'payment/' . $payment->getMethod() . '/order_status',
                ScopeInterface::SCOPE_STORE
            );

            $order->addStatusHistoryComment('', $status);
        }
    }

    /**
     * Determine whether order is in the right state for status override.
     *
     * @param \Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magento\Sales\Api\Data\OrderPaymentInterface $payment
     * @return bool
     */
    public function canSetOrderStatus(
        OrderInterface $order,
        OrderPaymentInterface $payment
    ) {
        // Do not allow status change if transaction is incomplete
        if ($payment->getIsTransactionPending()) {
            return false;
        }

        // Or order was held for fraud
        if ($payment->getIsFraudDetected()) {
            return false;
        }

        // Or order state is not processing (possibly redundant)
        if ($order->getState() !== Order::STATE_PROCESSING) {
            return false;
        }

        // Or payment method is not a tokenbase one
        if (!in_array($payment->getMethod(), $this->helper->getAllMethods())) {
            return false;
        }

        return true;
    }
}
