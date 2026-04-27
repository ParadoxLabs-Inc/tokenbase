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

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use ParadoxLabs\TokenBase\Helper\Data;
use ParadoxLabs\TokenBase\Model\Method\Factory;
use Throwable;

/**
 * CheckoutFailureVoidTransactionObserver Observer
 */
class CheckoutFailureVoidTransactionObserver implements ObserverInterface
{
    protected const FAILED_ORDER_STATES
        = [
            Order::STATE_CANCELED,
            Order::STATE_CLOSED,
        ];

    /**
     * @param Data $helper
     * @param Factory $methodFactory
     */
    public function __construct(
        protected readonly Data $helper,
        protected readonly Factory $methodFactory,
    ) {
    }

    /**
     * If checkout failed, void any transaction that went through.
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getData('order');

        if ($order instanceof Order === false
            || $order->getPayment() instanceof Payment === false
            || empty($order->getPayment()->getLastTransId())
            || in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === false
            || $order->getData('_tokenbase_saved_order') === true) {
            return;
        }

        // If the order has an ID and a valid state (saved successfully), don't void.
        if ($order->getId() > 0
            && in_array($order->getState(), static::FAILED_ORDER_STATES, true) === false) {
            return;
        }

        try {
            $method  = $this->methodFactory->getMethodInstance($order->getPayment()->getMethod());
            $gateway = $method->gateway();

            $response = $gateway->void(
                $order->getPayment(),
                $order->getPayment()->getLastTransId()
            );

            $this->helper->log(
                $order->getPayment()->getMethod(),
                'Auto-voided transaction due to checkout failure: ' . $response->toJson()
            );
        } catch (Throwable $e) {
            // Log and ignore any errors; we don't want to throw them in this context.
            $this->helper->log(
                $order->getPayment()->getMethod(),
                'Checkout post-failure void failed: ' . $e->getMessage()
            );
        }
    }
}
