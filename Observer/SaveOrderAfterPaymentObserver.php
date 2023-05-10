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
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Observer;

/**
 * SaveOrderAfterPaymentObserver Class
 */
class SaveOrderAfterPaymentObserver implements \Magento\Framework\Event\ObserverInterface
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
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * Plugin constructor
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->helper = $helper;
        $this->scopeConfig = $scopeConfig;
        $this->orderRepository = $orderRepository;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Save order/quote after successful payment processing, if enabled
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->isCheckoutSaveEnabled() !== true) {
            return;
        }

        /** @var \Magento\Sales\Model\Order\Payment $payment */
        /** @var \Magento\Sales\Model\Order $order */
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
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $enabled;
    }

    /**
     * Is the data we received good for processing? Must be the right models and a Tokenbase payment.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return bool
     */
    protected function isCheckoutSaveEligible($order): bool
    {
        return $order instanceof \Magento\Sales\Model\Order === true
            && $order->getPayment() instanceof \Magento\Sales\Model\Order\Payment === true
            && in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === true;
    }

    /**
     * Perform the order saving.
     *
     * @param \Magento\Sales\Model\Order $order
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function saveOrder(\Magento\Sales\Model\Order $order): void
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
