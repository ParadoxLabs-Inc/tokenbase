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

namespace ParadoxLabs\TokenBase\Plugin\Checkout\Model\GuestPaymentInformationManagement;

class Plugin
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var \Magento\Sales\Api\Data\OrderInterface
     */
    protected $order;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Plugin constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Checkout\Model\Session $checkoutSession *Proxy
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * If "Save new order immediately after payment" is enabled, silence any post-processing exceptions, so that the
     * customer gets a success page and knows the order was received.
     *
     * @param \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param \Magento\Quote\Api\Data\PaymentInterface $paymentMethod
     * @param \Magento\Quote\Api\Data\AddressInterface|null $billingAddress
     * @return mixed
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\GuestPaymentInformationManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        $email,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        ?\Magento\Quote\Api\Data\AddressInterface $billingAddress = null
    ) {
        try {
            return $proceed($cartId, $email, $paymentMethod, $billingAddress);
        } catch (\Exception $exception) {
            if ($this->orderWasSaved()) {
                $order = $this->getOrder();

                // Record the exception having occurred.
                $this->helper->log(
                    $order->getPayment()->getMethod(),
                    sprintf(
                        'Checkout exception suppressed for order %s: %s',
                        $order->getIncrementId(),
                        $exception->getMessage()
                    )
                );

                // Ensure the checkout exception gets logged to exception.log, including trace -- sometimes they're not.
                $this->logger->error((string)$exception, ['exception' => $exception]);

                return $order->getId();
            }

            throw $exception;
        }
    }

    /**
     * Was the order saved by us prior to the exception?
     *
     * @return bool
     */
    protected function orderWasSaved(): bool
    {
        if ($this->isCheckoutSaveEnabled() === false
            || $this->helper->getIsFrontend() === false
            || $this->isCheckoutSaveEligible() === false
            || $this->getOrder()->getData('_tokenbase_saved_order') !== true) {
            return false;
        }

        return true;
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
     * @return bool
     */
    protected function isCheckoutSaveEligible(): bool
    {
        $order = $this->getOrder();

        return $order instanceof \Magento\Sales\Model\Order === true
            && $order->getPayment() instanceof \Magento\Sales\Model\Order\Payment === true
            && in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === true;
    }

    /**
     * Get the order from the checkout session, if possible
     *
     * @return \Magento\Sales\Api\Data\OrderInterface|null
     */
    protected function getOrder()
    {
        if ($this->order !== null) {
            return $this->order;
        }

        $orderId = $this->checkoutSession->getLastOrderId();
        if (!empty($orderId)
            && $this->helper->getIsFrontend() === true) {
            $this->order = $this->orderRepository->get($orderId);
        }

        return $this->order;
    }
}
