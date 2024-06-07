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

use Magento\Quote\Api\Data\PaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;

/**
 * Custom data field conversion -- quote to order, etc, etc.
 */
class ConvertQuoteToOrderObserver extends ConvertAbstract implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * ConvertQuoteToOrderObserver constructor.
     *
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Framework\App\State $appState
     * @param \Magento\Framework\App\ResourceConnection $resource
     */
    public function __construct(
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Framework\App\State $appState,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->helper = $helper;
        $this->appState = $appState;
        $this->resource = $resource;
    }

    /**
     * Perform pre-order-placement actions.
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote  = $observer->getEvent()->getData('quote');

        /** @var \Magento\Sales\Model\Order $order */
        $order  = $observer->getEvent()->getData('order');

        if (in_array($order->getPayment()->getMethod(), $this->helper->getAllMethods(), true) !== true) {
            return;
        }

        /**
         * Copy fields from quote payment to order payment. If using GraphQL, set tokenbase_id.
         */
        $payment = $quote->getPayment();

        if ((defined('\Magento\Framework\App\Area::AREA_GRAPHQL')
            && $this->appState->getAreaCode() === \Magento\Framework\App\Area::AREA_GRAPHQL)) {
            if (!$payment->getData('tokenbase_id')) {
                $paymentAttributes = $payment->getExtensionAttributes();
                if ($paymentAttributes instanceof PaymentExtensionInterface && $paymentAttributes->getTokenbaseId()) {
                    $tokenbaseId = $paymentAttributes->getTokenbaseId();
                    $payment->setData('tokenbase_id', $tokenbaseId);
                    $order->getPayment()->setData('tokenbase_id', $tokenbaseId);

                    $orderPaymentExtn = $order->getPayment()->getExtensionAttributes();
                    if ($orderPaymentExtn instanceof OrderPaymentExtensionInterface) {
                        $orderPaymentExtn->setTokenbaseId($tokenbaseId);
                    }
                }
            }
        }

        $this->copyData(
            $payment,
            $order->getPayment()
        );

        /**
         * Persist increment_id -- if it's changed, it's new
         */
        if ($quote->getId()
            && empty($quote->getOrigData('reserved_order_id'))
            && !empty($quote->getReservedOrderId())) {
            // Save quote.reserved_order_id directly to the DB with no other interaction -- only efficient option.
            $connection = $this->resource->getConnection('checkout');
            $connection->update(
                $this->resource->getTableName('quote'),
                [
                    'reserved_order_id' => $quote->getReservedOrderId(),
                ],
                [
                    'entity_id=?' => $quote->getId(),
                ]
            );
        }
    }
}
