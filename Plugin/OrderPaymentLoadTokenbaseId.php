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

use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;

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
     * @param \Magento\Sales\Model\Order\Payment $subject
     * @param OrderPaymentExtensionInterface|null $result
     * @return OrderPaymentExtensionInterface|null
     */
    public function afterGetExtensionAttributes(
        \Magento\Sales\Model\Order\Payment $subject,
        $result
    ) {
        if ($result instanceof OrderPaymentExtensionInterface === false) {
            $result = $this->orderPaymentExtensionFactory->create();
        }
        $result->setTokenbaseId($subject->getData('tokenbase_id'));

        $subject->setExtensionAttributes($result);

        return $result;
    }

    /**
     * @param \Magento\Sales\Model\Order\Payment $subject
     * @param array|null|mixed $result
     * @return array|null|mixed
     */
    public function afterGetAdditionalInformation(
        \Magento\Sales\Model\Order\Payment $subject,
        $result
    ) {
        // Trigger loading of extension attributes, because the above doesn't happen on its own during REST order load.
        $subject->getExtensionAttributes();

        return $result;
    }
}
