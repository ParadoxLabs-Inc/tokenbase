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

namespace ParadoxLabs\TokenBase\Plugin;

/**
 * OrderPaymentLoadTokenbaseId Plugin
 *
 * Load tokenbase_id to ExtensionAttributes for order.
 */
class OrderPaymentLoadTokenbaseId
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
     * @param \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory
     * @param \ParadoxLabs\TokenBase\Helper\Data $helper
     */
    public function __construct(
        \Magento\Sales\Api\Data\OrderPaymentExtensionFactory $orderPaymentExtensionFactory,
        \ParadoxLabs\TokenBase\Helper\Data $helper
    ) {
        $this->orderPaymentExtensionFactory = $orderPaymentExtensionFactory;
        $this->helper = $helper;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    private function setExtensionAttributeValue(\Magento\Sales\Model\Order $order)
    {
        $payment = $order->getPayment();
        if ($payment instanceof \Magento\Sales\Api\Data\OrderPaymentInterface === false) {
            return;
        }

        $paymentExtension = $payment->getExtensionAttributes();
        if ($paymentExtension === null) {
            $paymentExtension = $this->orderPaymentExtensionFactory->create();
        }
        $paymentExtension->setTokenbaseId($payment->getData('tokenbase_id'));

        $payment->setExtensionAttributes($paymentExtension);
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @param \Magento\Sales\Model\Order $result
     * @return \Magento\Sales\Model\Order
     */
    public function afterLoad(
        \Magento\Sales\Model\Order $subject,
        \Magento\Sales\Model\Order $result
    ) {
        $this->setExtensionAttributeValue($result);

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order $subject
     * @return void
     */
    public function beforePlace(
        \Magento\Sales\Model\Order $subject
    ) {
        $this->setExtensionAttributeValue($subject);
    }
}
