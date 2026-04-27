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

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Helper\Data;

class SaveOrderAfterPaymentObserver implements ObserverInterface
{
    /**
     * Plugin constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param OrderRepositoryInterface $orderRepository
     * @param CartRepositoryInterface $cartRepository
     * @param Session $checkoutSession *Proxy
     */
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly Data $helper,
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly CartRepositoryInterface $cartRepository,
        protected readonly Session $checkoutSession,
    ) {
    }

    /**
     * Save order/quote after successful payment processing, if enabled
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->isCheckoutSaveEnabled() !== true) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var Order $order */
        $payment = $observer->getData('payment');
        $order   = $payment->getOrder();

        if ($this->isCheckoutSaveEligible($order) !== true) {
            return;
        }

        $this->saveOrder($order);
    }

    /**
     * Is this save enabled in config?
     *
     * @return bool
     */
    protected function isCheckoutSaveEnabled(): bool
    {
        $enabled = (bool)$this->scopeConfig->getValue(
            'checkout/tokenbase/save_order_after_payment',
            ScopeInterface::SCOPE_STORE
        );

        return $enabled;
    }

    /**
     * Is the data we received good for processing? Must be the right models and a Tokenbase payment.
     *
     * @param Order $order
     * @return bool
     */
    protected function isCheckoutSaveEligible($order): bool
    {
        return $order instanceof Order === true
            && $order->getPayment() instanceof Payment === true
            && in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === true;
    }

    /**
     * Perform the order saving.
     *
     * @param Order $order
     * @return void
     * @throws NoSuchEntityException
     */
    protected function saveOrder(Order $order): void
    {
        $this->orderRepository->save($order);
        $order->setData('_tokenbase_saved_order', true);

        if (!empty($order->getQuoteId())) {
            $quote = $this->cartRepository->get($order->getQuoteId());
            $quote->setIsActive(false);
            $this->cartRepository->save($quote);
        }

        if ($this->helper->getIsFrontend()) {
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastOrderId($order->getId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
            $this->checkoutSession->setLastOrderStatus($order->getStatus());
        }
    }
}
