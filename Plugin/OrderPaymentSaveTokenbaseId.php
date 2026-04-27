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

namespace ParadoxLabs\TokenBase\Plugin;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Api\Data\OrderPaymentExtensionInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use ParadoxLabs\TokenBase\Helper\Data;

/**
 * OrderPaymentSaveTokenbaseId Plugin
 *
 * Save tokenbase_id from ExtensionAttributes for order.
 */
class OrderPaymentSaveTokenbaseId
{
    /**
     * @param Data $helper
     */
    public function __construct(protected readonly Data $helper)
    {
    }

    /**
     * @param OrderRepositoryInterface $subject
     * @param OrderInterface $order
     * @return array
     * @throws CouldNotSaveException
     */
    public function beforeSave(
        OrderRepositoryInterface $subject,
        OrderInterface $order
    ) {
        /** @var Payment $payment */
        $payment = $order->getPayment();
        if ($payment instanceof OrderPaymentInterface === false) {
            return [$order];
        }

        /** @var OrderPaymentExtensionInterface $extendedAttributes */
        $extendedAttributes = $payment->getExtensionAttributes();
        if ($extendedAttributes === null) {
            $tokenbaseId = $payment->getData('tokenbase_id');
        } else {
            $tokenbaseId = $extendedAttributes->getTokenbaseId();
        }

        if ($tokenbaseId !== null
            && $tokenbaseId != $payment->getOrigData('tokenbase_id')
            && $tokenbaseId != $payment->getData('tokenbase_id')) {
            $payment->setData('tokenbase_id', $tokenbaseId);
        }

        return [$order];
    }
}
