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

namespace ParadoxLabs\TokenBase\Plugin\Checkout\Model\PaymentInformationManagement;

use Magento\Sales\Api\Data\OrderInterface;
use Closure;
use Magento\Checkout\Api\PaymentInformationManagementInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\ScopeInterface;
use ParadoxLabs\TokenBase\Helper\Data;
use Psr\Log\LoggerInterface;
use Throwable;

class Plugin
{
    /**
     * @var OrderInterface
     */
    protected $order;

    /**
     * Plugin constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param Data $helper
     * @param Session $checkoutSession *Proxy
     * @param OrderRepositoryInterface $orderRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        protected readonly ScopeConfigInterface $scopeConfig,
        protected readonly Data $helper,
        protected readonly Session $checkoutSession,
        protected readonly OrderRepositoryInterface $orderRepository,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * If "Save new order immediately after payment" is enabled, silence any post-processing exceptions, so that the
     * customer gets a success page and knows the order was received.
     *
     * @param PaymentInformationManagementInterface $subject
     * @param \Closure $proceed
     * @param $cartId
     * @param PaymentInterface $paymentMethod
     * @param AddressInterface|null $billingAddress
     * @return mixed
     */
    public function aroundSavePaymentInformationAndPlaceOrder(
        PaymentInformationManagementInterface $subject,
        Closure $proceed,
        $cartId,
        PaymentInterface $paymentMethod,
        ?AddressInterface $billingAddress = null
    ) {
        try {
            return $proceed($cartId, $paymentMethod, $billingAddress);
        } catch (Throwable $exception) {
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
            ScopeInterface::SCOPE_STORE
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

        return $order instanceof Order === true
            && $order->getPayment() instanceof Payment === true
            && in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) === true;
    }

    /**
     * Get the order from the checkout session, if possible
     *
     * @return OrderInterface|null
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
