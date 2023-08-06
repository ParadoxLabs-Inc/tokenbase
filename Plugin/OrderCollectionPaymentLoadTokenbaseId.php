<?php
/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *   http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
 */

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * Load tokenbase_id to ExtensionAttributes for order.
 */
class OrderCollectionPaymentLoadTokenbaseId
{
    /**
     * @var \Magento\Sales\Api\Data\OrderPaymentExtensionFactory
     */
    protected $orderPaymentExtensionFactory;

    /**
     * @var \ParadoxLabs\TokenBase\Helper\Data
     */
    protected $helper;

    /**
     * @var \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory
     */
    private $orderPaymentCollectionFactory;

    /**
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     * @param \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $orderPaymentCollectionFactory
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper,
        \Magento\Sales\Model\ResourceModel\Order\Payment\CollectionFactory $orderPaymentCollectionFactory
    ) {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
        $this->orderPaymentCollectionFactory = $orderPaymentCollectionFactory;
        $this->helper = $helper;
    }

    public function afterLoadWithFilter(
        \Magento\Sales\Model\ResourceModel\Order\Collection $subject,
        \Magento\Sales\Model\ResourceModel\Order\Collection $collection
    ): \Magento\Sales\Model\ResourceModel\Order\Collection {
        $orderPaymentCache = $this->prefetchOrderPayments($collection);

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            $orderId = $order->getId();
            $orderPayment = $orderPaymentCache[$orderId] ?? null;

            if ($orderPayment && $this->isTokenbaseMethod($orderPayment)) {
                $order->setPayment($orderPayment);

                $paymentExtension = $orderPayment->getExtensionAttributes();
                if ($paymentExtension === null) {
                    $paymentExtension = $this->orderPaymentExtensionFactory->create();
                }
                $paymentExtension->setTokenbaseId($orderPayment->getData('tokenbase_id'));

                $orderPayment->setExtensionAttributes($paymentExtension);
            }
        }

        return $collection;
    }

    /**
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $collection
     * @return array
     */
    public function prefetchOrderPayments(\Magento\Sales\Model\ResourceModel\Order\Collection $collection): array
    {
        $orderIds = array_filter(array_unique($collection->getAllIds()));

        /** @var \Magento\Sales\Model\ResourceModel\Order\Payment\Collection $orderPaymentCollection */
        $orderPaymentCollection = $this->orderPaymentCollectionFactory->create();
        $orderPaymentCollection->setOrderFilter(['in' => $orderIds]);

        $orderPaymentCache = [];
        /** @var \Magento\Sales\Api\Data\OrderPaymentInterface $orderPayment */
        foreach ($orderPaymentCollection->getItems() as $orderPayment) {
            $orderPaymentCache[$orderPayment->getParentId()] = $orderPayment;
        }

        /** @var \Magento\Sales\Model\Order $order */
        foreach ($collection->getItems() as $order) {
            $orderId = $order->getId();

            if (isset($orderPaymentCache[$orderId])) {
                $orderPayment = $orderPaymentCache[$orderId];
                $order->setPayment($orderPayment);
            }
        }

        return $orderPaymentCache;
    }

    /**
     * @param \Magento\Framework\DataObject|\Magento\Sales\Api\Data\OrderPaymentInterface $orderPayment
     * @return bool
     */
    public function isTokenbaseMethod(\Magento\Sales\Api\Data\OrderPaymentInterface $orderPayment): bool {
        return in_array($orderPayment, $this->helper->getAllMethods());
    }
}
